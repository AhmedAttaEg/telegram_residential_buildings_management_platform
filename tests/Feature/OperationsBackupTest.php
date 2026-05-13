<?php

namespace Tests\Feature;

use App\Contracts\ShellCommandRunner;
use App\Models\Tenant;
use App\Services\Operations\BackupService;
use App\Support\ShellCommandResult;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsBackupTest extends TestCase
{
    private string $sqlitePath;

    private string $backupRoot;

    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);
        $this->sqlitePath = storage_path('framework/testing/operations-backup.sqlite');
        $this->backupRoot = storage_path('framework/testing/backups');

        $this->files->delete($this->sqlitePath);
        $this->files->deleteDirectory($this->backupRoot);
        $this->files->ensureDirectoryExists(dirname($this->sqlitePath));
        $this->files->put($this->sqlitePath, '');
        $this->files->ensureDirectoryExists($this->backupRoot);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $this->sqlitePath,
            'operations.backups.root' => $this->backupRoot,
        ]);

        DB::purge('sqlite');

        Artisan::call('migrate:fresh', [
            '--database' => 'sqlite',
            '--force' => true,
        ]);
    }

    protected function tearDown(): void
    {
        $this->files->delete($this->sqlitePath);
        $this->files->deleteDirectory($this->backupRoot);
        $this->files->delete(storage_path('app/public/operations-restore.txt'));

        parent::tearDown();
    }

    public function test_combined_backup_creates_manifest_database_copy_and_storage_archive(): void
    {
        Tenant::factory()->create([
            'name' => 'Backup Tenant',
        ]);

        $this->files->ensureDirectoryExists(storage_path('app/public'));
        $this->files->put(storage_path('app/public/operations-restore.txt'), 'before-backup');

        $this->artisan('backups:run')
            ->expectsOutputToContain('Backup created successfully:')
            ->assertExitCode(0);

        $backupId = $this->latestBackupId();
        $backupPath = $this->backupRoot.DIRECTORY_SEPARATOR.$backupId;

        $this->assertFileExists($backupPath.DIRECTORY_SEPARATOR.'manifest.json');
        $this->assertFileExists($backupPath.DIRECTORY_SEPARATOR.'database.sqlite');
        $this->assertFileExists($backupPath.DIRECTORY_SEPARATOR.'storage.tar');

        $manifest = json_decode((string) file_get_contents($backupPath.DIRECTORY_SEPARATOR.'manifest.json'), true);

        $this->assertSame('sqlite', $manifest['components']['database']['driver']);
        $this->assertSame('phar', $manifest['components']['storage']['driver']);
    }

    public function test_restore_rehydrates_database_and_storage_from_backup(): void
    {
        Tenant::factory()->create([
            'name' => 'Restored Tenant',
        ]);

        $this->files->ensureDirectoryExists(storage_path('app/public'));
        $this->files->put(storage_path('app/public/operations-restore.txt'), 'restorable-content');

        $this->artisan('backups:run')->assertExitCode(0);

        $backupId = $this->latestBackupId();

        Tenant::query()->delete();
        $this->files->put(storage_path('app/public/operations-restore.txt'), 'mutated-content');

        $this->assertDatabaseCount('tenants', 0);
        $this->assertSame('mutated-content', (string) file_get_contents(storage_path('app/public/operations-restore.txt')));

        $this->artisan('backups:restore', [
            'backup' => $backupId,
            '--force' => true,
        ])
            ->expectsOutput("Backup restored successfully: {$backupId}")
            ->expectsOutput('Health verification: OK')
            ->assertExitCode(0);

        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseHas('tenants', [
            'name' => 'Restored Tenant',
        ]);
        $this->assertSame('restorable-content', (string) file_get_contents(storage_path('app/public/operations-restore.txt')));
    }

    public function test_cleanup_prunes_old_daily_backups_and_keeps_weekly_snapshots(): void
    {
        $service = app(BackupService::class);
        $oldest = now()->subDays(40);
        $weeklyKept = now()->subDays(19);
        $weeklyPruned = now()->subDays(20);
        $dailyKept = now()->subDays(3);

        foreach ([
            $oldest,
            $weeklyPruned,
            $weeklyKept,
            $dailyKept,
        ] as $date) {
            $backupId = $date->format('Ymd_His');
            $path = $this->backupRoot.DIRECTORY_SEPARATOR.$backupId;
            $this->files->ensureDirectoryExists($path);
            $this->files->put($path.DIRECTORY_SEPARATOR.'manifest.json', json_encode([
                'id' => $backupId,
                'created_at' => $date->toIso8601String(),
                'components' => [],
            ], JSON_PRETTY_PRINT));
        }

        $deleted = $service->cleanup();

        $this->assertSame(2, $deleted);
        $remaining = collect($this->files->directories($this->backupRoot))
            ->map(fn (string $path): string => basename($path))
            ->values()
            ->all();

        $this->assertContains($dailyKept->format('Ymd_His'), $remaining);
        $this->assertContains($weeklyKept->format('Ymd_His'), $remaining);
        $this->assertNotContains($weeklyPruned->format('Ymd_His'), $remaining);
        $this->assertNotContains($oldest->format('Ymd_His'), $remaining);
    }

    public function test_mysql_database_backup_uses_the_configured_shell_binaries(): void
    {
        $captured = [];

        app()->instance(ShellCommandRunner::class, new class($captured) implements ShellCommandRunner {
            /**
             * @param  array<int, array<int, string>>  $captured
             */
            public function __construct(private array &$captured)
            {
            }

            public function run(array $command, ?string $workingDirectory = null, array $environment = []): ShellCommandResult
            {
                $this->captured[] = $command;

                foreach ($command as $segment) {
                    if (str_starts_with($segment, '--result-file=')) {
                        file_put_contents(substr($segment, 14), '-- mock sql dump --');
                    }
                }

                return new ShellCommandResult(0, 'ok');
            }
        });

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'hostinger_staging_database',
                'username' => 'backup_user',
                'password' => 'secret',
                'charset' => 'utf8mb4',
            ],
        ]);

        $result = app(BackupService::class)->run(true, false);

        $this->assertNotEmpty($captured);
        $this->assertSame('mysqldump', $captured[0][0]);
        $this->assertContains('--host=127.0.0.1', $captured[0]);
        $this->assertContains('--user=backup_user', $captured[0]);
        $this->assertContains('hostinger_staging_database', $captured[0]);
        $this->assertFileExists($result['path'].DIRECTORY_SEPARATOR.'database.sql');
    }

    private function latestBackupId(): string
    {
        return collect($this->files->directories($this->backupRoot))
            ->map(fn (string $path): string => basename($path))
            ->sortDesc()
            ->firstOrFail();
    }
}
