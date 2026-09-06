<?php

namespace App\Modules\System\Services;

use App\Enums\PowerAction;
use App\Modules\System\Contracts\PowerManager;
use App\Modules\System\Exceptions\PowerException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final readonly class ShellPowerManager implements PowerManager
{
    /**
     * @param  array<string, string>  $commands  Power action value to shell command.
     */
    public function __construct(
        private array $commands,
        private int $timeout = 10,
    ) {}

    public function perform(PowerAction $action): void
    {
        $command = trim($this->commands[$action->value] ?? '');

        if ($command === '') {
            throw PowerException::notConfigured($action);
        }

        $process = Process::fromShellCommandline($command);
        $process->setTimeout($this->timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw PowerException::failed($action, $exception->getMessage());
        }

        if (! $process->isSuccessful()) {
            throw PowerException::failed($action, trim($process->getErrorOutput()) ?: 'невідома помилка');
        }
    }
}
