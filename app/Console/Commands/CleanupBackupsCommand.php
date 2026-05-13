<?php

namespace App\Console\Commands;

use App\Services\Operations\BackupService;
use Illuminate\Console\Command;
use Throwable;

class CleanupBackupsCommand extends Command
{
    protected $signature = 'backups:cleanup';

    protected $description = 'Delete expired backup sets according to the configured retention policy.';

    public function __construct(
        private readonly BackupService $backupService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $deleted = $this->backupService->cleanup();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Expired backup sets deleted: %d', $deleted));

        return self::SUCCESS;
    }
}
