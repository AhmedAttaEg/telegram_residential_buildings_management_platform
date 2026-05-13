<?php

namespace App\Support;

use App\Contracts\ShellCommandRunner;
use Symfony\Component\Process\Process;

class SymfonyShellCommandRunner implements ShellCommandRunner
{
    public function run(array $command, ?string $workingDirectory = null, array $environment = []): ShellCommandResult
    {
        $process = new Process(
            $command,
            $workingDirectory,
            $environment !== [] ? array_merge($_ENV, $environment) : null,
        );

        $process->run();

        return new ShellCommandResult(
            $process->getExitCode() ?? 1,
            $process->getOutput(),
            $process->getErrorOutput(),
        );
    }
}
