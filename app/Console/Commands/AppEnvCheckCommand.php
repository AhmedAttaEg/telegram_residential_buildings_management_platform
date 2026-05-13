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
    protected $signature = 'app:env-check
        {--file= : The environment file to validate}
        {--reference=.env.example : The reference environment template file}';

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
        $referencePath = $this->resolveEnvironmentPath((string) $this->option('reference'));
        $targetPath = $this->option('file') !== null
            ? $this->resolveEnvironmentPath((string) $this->option('file'))
            : $this->environmentFilePath();

        $exampleVariables = $this->parseEnvironmentFile($referencePath);
        $environmentVariables = $this->parseEnvironmentFile($targetPath);

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

    private function resolveEnvironmentPath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
