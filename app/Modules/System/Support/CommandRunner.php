<?php

namespace App\Modules\System\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Runs a read-only shell command and returns its output, or an empty string.
 */
final class CommandRunner
{
    /**
     * PHP-FPM often inherits a minimal PATH that misses /usr/sbin and /sbin,
     * which is where tools such as sysctl live. Metrics would silently read as
     * zero, so the search path is pinned instead of inherited.
     */
    private const string SEARCH_PATH = '/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin';

    public function __construct(private readonly int $timeout = 10) {}

    public function run(string $command): string
    {
        $process = Process::fromShellCommandline($command, env: ['PATH' => self::SEARCH_PATH]);
        $process->setTimeout($this->timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('System metrics command failed.', [
                'command' => $command,
                'exit_code' => $process->getExitCode(),
                'error' => trim($process->getErrorOutput()),
            ]);

            return '';
        }

        return $process->getOutput();
    }
}
