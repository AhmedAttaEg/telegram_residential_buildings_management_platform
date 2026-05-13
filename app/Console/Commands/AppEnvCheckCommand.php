<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppEnvCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:env-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that all variables documented in .env.example exist in the active environment file.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $exampleVariables = $this->parseEnvironmentFile(base_path('.env.example'));
        $environmentVariables = $this->parseEnvironmentFile($this->environmentFilePath());

        $missingVariables = array_values(array_diff(array_keys($exampleVariables), array_keys($environmentVariables)));

        if ($missingVariables === []) {
            $this->info('Environment variables are complete.');

            return self::SUCCESS;
        }

        $this->error('Missing environment variables: '.implode(', ', $missingVariables));

        return self::FAILURE;
    }

    /**
     * @return array<string, string>
     */
    private function parseEnvironmentFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $variables = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#') || ! str_contains($trimmedLine, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmedLine, 2);
            $variables[trim($key)] = trim($value);
        }

        return $variables;
    }

    private function environmentFilePath(): string
    {
        if (app()->environment('testing') && is_file(base_path('.env.testing'))) {
            return base_path('.env.testing');
        }

        return base_path(app()->environmentFile());
    }
}
