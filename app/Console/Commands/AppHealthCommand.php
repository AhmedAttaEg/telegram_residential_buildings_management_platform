<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class AppHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Laravel boot and database connectivity.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Health report');
        $this->line('Application: OK');

        try {
            DB::connection()->select('SELECT 1');

            $this->line(sprintf(
                'Database: OK (%s)',
                DB::getDefaultConnection(),
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line(sprintf(
                'Database: FAIL (%s)',
                DB::getDefaultConnection(),
            ));
            $this->line(sprintf('Reason: %s', $exception->getMessage()));

            return self::FAILURE;
        }
    }
}
