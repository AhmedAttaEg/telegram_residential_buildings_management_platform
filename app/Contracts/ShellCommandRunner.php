<?php

namespace App\Contracts;

use App\Support\ShellCommandResult;

interface ShellCommandRunner
{
    /**
     * @param  array<int, string>  $command
     * @param  array<string, string>  $environment
     */
    public function run(array $command, ?string $workingDirectory = null, array $environment = []): ShellCommandResult;
}
