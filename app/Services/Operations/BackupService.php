<?php

namespace App\Services\Operations;

use App\Contracts\ShellCommandRunner;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Phar;
use PharData;
use RuntimeException;

class BackupService
{
    public function __construct(
        private readonly ShellCommandRunner $commandRunner,
        private readonly Filesystem $files,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(bool $database = true, bool $storage = true): array
    {
        if (! $database && ! $storage) {
            throw new RuntimeException('At least one backup component must be selected.');
        }

        $createdAt = CarbonImmutable::now();
        $backupId = $createdAt->format('Ymd_His');
        $backupPath = $this->backupPath($backupId);

        $this->files->ensureDirectoryExists($backupPath);

        $manifest = [
            'id' => $backupId,
            'created_at' => $createdAt->toIso8601String(),
            'app_env' => config('app.env'),
            'app_url' => config('app.url'),
            'database_connection' => config('database.default'),
            'components' => [],
        ];

        if ($database) {
            $manifest['components']['database'] = $this->backupDatabase($backupPath);
        }

        if ($storage) {
            $manifest['components']['storage'] = $this->backupStorage($backupPath);
        }

        $manifestPath = $backupPath.DIRECTORY_SEPARATOR.'manifest.json';
        $this->files->put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return [
            'id' => $backupId,
            'path' => $backupPath,
            'manifest_path' => $manifestPath,
            'manifest' => $manifest,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function restore(string $backupId, bool $database = true, bool $storage = true): array
    {
        if (! $database && ! $storage) {
            throw new RuntimeException('At least one restore component must be selected.');
        }

        $manifest = $this->manifest($backupId);

        if ($database) {
            $component = $manifest['components']['database'] ?? null;

            if (! is_array($component)) {
                throw new RuntimeException('Database backup component is missing.');
            }

            $this->restoreDatabase($backupId, $component);
        }

        if ($storage) {
            $component = $manifest['components']['storage'] ?? null;

            if (! is_array($component)) {
                throw new RuntimeException('Storage backup component is missing.');
            }

            $this->restoreStorage($backupId, $component);
        }

        DB::connection()->select('SELECT 1');

        return [
            'id' => $backupId,
            'manifest' => $manifest,
        ];
    }

    public function cleanup(): int
    {
        $backupSets = collect($this->backupDirectories())
            ->map(fn (string $path): ?array => $this->loadBackupSummary($path))
            ->filter()
            ->sortByDesc('created_at')
            ->values();

        $dailyThreshold = CarbonImmutable::now()->subDays((int) config('operations.backups.retention.daily_days', 7));
        $weeklyThreshold = CarbonImmutable::now()->subWeeks((int) config('operations.backups.retention.weekly_weeks', 4));
        $keptWeeklyBuckets = [];
        $deleted = 0;

        foreach ($backupSets as $backup) {
            $createdAt = $backup['created_at'];

            if ($createdAt->greaterThanOrEqualTo($dailyThreshold)) {
                continue;
            }

            if ($createdAt->lessThan($weeklyThreshold)) {
                $this->files->deleteDirectory($backup['path']);
                $deleted++;

                continue;
            }

            $bucket = $createdAt->format('o-W');

            if (! isset($keptWeeklyBuckets[$bucket])) {
                $keptWeeklyBuckets[$bucket] = true;

                continue;
            }

            $this->files->deleteDirectory($backup['path']);
            $deleted++;
        }

        return $deleted;
    }

    public function backupRoot(): string
    {
        $configured = (string) config('operations.backups.root', storage_path('app/backups'));

        if (Str::startsWith($configured, [DIRECTORY_SEPARATOR]) || preg_match('/^[A-Za-z]:\\\\/', $configured) === 1) {
            return $configured;
        }

        return base_path($configured);
    }

    /**
     * @return array<string, mixed>
     */
    public function manifest(string $backupId): array
    {
        $manifestPath = $this->backupPath($backupId).DIRECTORY_SEPARATOR.'manifest.json';

        if (! $this->files->exists($manifestPath)) {
            throw new RuntimeException(sprintf('Backup manifest [%s] does not exist.', $backupId));
        }

        $manifest = json_decode((string) $this->files->get($manifestPath), true);

        if (! is_array($manifest)) {
            throw new RuntimeException(sprintf('Backup manifest [%s] is invalid.', $backupId));
        }

        return $manifest;
    }

    private function backupPath(string $backupId): string
    {
        return $this->backupRoot().DIRECTORY_SEPARATOR.$backupId;
    }

    /**
     * @return array<string, mixed>
     */
    private function backupDatabase(string $backupPath): array
    {
        $driver = (string) config('database.default');

        if ($driver === 'sqlite') {
            $databasePath = (string) config('database.connections.sqlite.database');

            if ($databasePath === '' || $databasePath === ':memory:') {
                throw new RuntimeException('SQLite backups require a file-based database path.');
            }

            $target = $backupPath.DIRECTORY_SEPARATOR.'database.sqlite';
            $this->files->copy($databasePath, $target);

            return [
                'driver' => 'sqlite',
                'path' => basename($target),
            ];
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException(sprintf('Database backups are not configured for [%s].', $driver));
        }

        $target = $backupPath.DIRECTORY_SEPARATOR.'database.sql';
        $connection = config("database.connections.{$driver}");
        $binary = (string) config('operations.backups.database.dump_binary', 'mysqldump');

        $result = $this->commandRunner->run([
            $binary,
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            '--default-character-set='.$connection['charset'],
            '--result-file='.$target,
            (string) $connection['database'],
        ], base_path(), [
            'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
        ]);

        if (! $result->successful()) {
            throw new RuntimeException('Database backup command failed: '.trim($result->errorOutput ?: $result->output));
        }

        return [
            'driver' => $driver,
            'path' => basename($target),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function backupStorage(string $backupPath): array
    {
        $storageRoot = storage_path();
        $archivePath = $backupPath.DIRECTORY_SEPARATOR.'storage.tar';
        $phar = new PharData($archivePath);
        $phar->startBuffering();

        foreach ((array) config('operations.backups.storage.include', ['app', 'framework', 'logs']) as $relativePath) {
            $sourcePath = $storageRoot.DIRECTORY_SEPARATOR.$relativePath;

            if (! $this->files->exists($sourcePath)) {
                continue;
            }

            $this->addPathToArchive($phar, $sourcePath, $relativePath);
        }

        $phar->stopBuffering();

        return [
            'driver' => 'phar',
            'path' => basename($archivePath),
            'include' => array_values((array) config('operations.backups.storage.include', ['app', 'framework', 'logs'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $component
     */
    private function restoreDatabase(string $backupId, array $component): void
    {
        $driver = (string) ($component['driver'] ?? config('database.default'));
        $source = $this->backupPath($backupId).DIRECTORY_SEPARATOR.(string) $component['path'];

        if (! $this->files->exists($source)) {
            throw new RuntimeException(sprintf('Database backup file [%s] is missing.', $source));
        }

        if ($driver === 'sqlite') {
            $databasePath = (string) config('database.connections.sqlite.database');

            if ($databasePath === '' || $databasePath === ':memory:') {
                throw new RuntimeException('SQLite restore requires a file-based database path.');
            }

            $this->files->ensureDirectoryExists(dirname($databasePath));
            $this->files->copy($source, $databasePath);
            DB::purge(config('database.default'));

            return;
        }

        $connection = config("database.connections.{$driver}");
        $binary = (string) config('operations.backups.database.restore_binary', 'mysql');

        $result = $this->commandRunner->run([
            $binary,
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            (string) $connection['database'],
            '--execute=source '.str_replace('\\', '/', $source),
        ], base_path(), [
            'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
        ]);

        if (! $result->successful()) {
            throw new RuntimeException('Database restore command failed: '.trim($result->errorOutput ?: $result->output));
        }
    }

    /**
     * @param  array<string, mixed>  $component
     */
    private function restoreStorage(string $backupId, array $component): void
    {
        $archivePath = $this->backupPath($backupId).DIRECTORY_SEPARATOR.(string) $component['path'];

        if (! $this->files->exists($archivePath)) {
            throw new RuntimeException(sprintf('Storage backup archive [%s] is missing.', $archivePath));
        }

        $tempPath = storage_path('framework/testing/restore-'.Str::uuid());
        $this->files->ensureDirectoryExists($tempPath);

        try {
            $archive = new PharData($archivePath);
            $archive->extractTo($tempPath, null, true);

            foreach ((array) ($component['include'] ?? []) as $relativePath) {
                $sourcePath = $tempPath.DIRECTORY_SEPARATOR.$relativePath;
                $targetPath = storage_path($relativePath);

                if (! $this->files->exists($sourcePath)) {
                    continue;
                }

                $this->files->deleteDirectory($targetPath);
                $this->files->copyDirectory($sourcePath, $targetPath);
            }

            $this->ensureLaravelStorageDirectories();
        } finally {
            $this->files->deleteDirectory($tempPath);
        }
    }

    private function ensureLaravelStorageDirectories(): void
    {
        foreach ([
            storage_path('app'),
            storage_path('app/public'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('framework/views'),
            storage_path('logs'),
        ] as $directory) {
            $this->files->ensureDirectoryExists($directory);
        }
    }

    private function addPathToArchive(PharData $archive, string $sourcePath, string $relativePath): void
    {
        $sourcePath = rtrim($sourcePath, DIRECTORY_SEPARATOR);

        if ($this->files->isFile($sourcePath)) {
            $archive->addFile($sourcePath, str_replace(DIRECTORY_SEPARATOR, '/', $relativePath));

            return;
        }

        $archive->addEmptyDir(str_replace(DIRECTORY_SEPARATOR, '/', $relativePath));

        foreach ($this->files->allFiles($sourcePath, true) as $file) {
            $realPath = $file->getPathname();
            $nestedRelativePath = $relativePath.DIRECTORY_SEPARATOR.ltrim(str_replace($sourcePath, '', $realPath), DIRECTORY_SEPARATOR);

            if ($this->shouldExcludeFromStorageBackup($nestedRelativePath)) {
                continue;
            }

            $archive->addFile($realPath, str_replace(DIRECTORY_SEPARATOR, '/', $nestedRelativePath));
        }
    }

    private function shouldExcludeFromStorageBackup(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);

        foreach ((array) config('operations.backups.storage.exclude', []) as $excludedPath) {
            $excluded = trim(str_replace('\\', '/', (string) $excludedPath), '/');

            if ($excluded !== '' && Str::startsWith($normalized, $excluded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function backupDirectories(): array
    {
        $root = $this->backupRoot();

        if (! $this->files->isDirectory($root)) {
            return [];
        }

        return array_values(array_filter($this->files->directories($root), function (string $path): bool {
            return $this->files->exists($path.DIRECTORY_SEPARATOR.'manifest.json');
        }));
    }

    /**
     * @return array{path:string,created_at:CarbonImmutable}|null
     */
    private function loadBackupSummary(string $path): ?array
    {
        $manifestPath = $path.DIRECTORY_SEPARATOR.'manifest.json';

        if (! $this->files->exists($manifestPath)) {
            return null;
        }

        $manifest = json_decode((string) $this->files->get($manifestPath), true);

        if (! is_array($manifest) || ! isset($manifest['created_at'])) {
            return null;
        }

        return [
            'path' => $path,
            'created_at' => CarbonImmutable::parse((string) $manifest['created_at']),
        ];
    }
}
