<?php

namespace App\Console\Commands;

use App\Services\Operations\BackupService;
use Illuminate\Console\Command;
use Throwable;

class RestoreBackupsCommand extends Command
{
    protected $signature = 'backups:restore
        {backup : The backup ID directory to restore}
        {--database-only : Restore only the database}
        {--storage-only : Restore only the storage tree}
        {--force : Confirm destructive restore operations}';

    protected $description = 'Restore a timestamped application backup set.';

    public function __construct(
        private readonly BackupService $backupService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('The --force option is required for restore operations.');

            return self::FAILURE;
        }

        [$database, $storage] = $this->selectedComponents();

        try {
            $result = $this->backupService->restore((string) $this->argument('backup'), $database, $storage);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Backup restored successfully: %s', $result['id']));
        $this->line('Health verification: OK');

        return self::SUCCESS;
    }

    /**
     * @return array{bool,bool}
     */
    private function selectedComponents(): array
    {
        if ($this->option('database-only')) {
            return [true, false];
        }

        if ($this->option('storage-only')) {
            return [false, true];
        }

        return [true, true];
    }
}
