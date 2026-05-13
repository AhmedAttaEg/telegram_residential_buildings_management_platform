<?php

namespace App\Console\Commands;

use App\Services\Operations\BackupService;
use Illuminate\Console\Command;
use Throwable;

class RunBackupsCommand extends Command
{
    protected $signature = 'backups:run {--database-only : Backup only the database} {--storage-only : Backup only the storage tree}';

    protected $description = 'Create a timestamped application backup set.';

    public function __construct(
        private readonly BackupService $backupService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        [$database, $storage] = $this->selectedComponents();

        try {
            $result = $this->backupService->run($database, $storage);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Backup created successfully: %s', $result['id']));
        $this->line(sprintf('Location: %s', $result['path']));

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
