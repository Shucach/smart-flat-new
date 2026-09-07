<?php

namespace App\Modules\System\Services;

use App\Enums\PowerAction;
use App\Modules\System\Contracts\PowerManager;
use App\Modules\System\Exceptions\PowerException;
use Illuminate\Support\Carbon;

/**
 * Asks the host to reboot or shut down by leaving a request file where the host
 * can see it.
 *
 * Production runs this application inside a container, which can never power the
 * board off itself: it has no sudo, and its /sbin/reboot is systemd talking to a
 * PID namespace that owns nothing. The container writes here instead, and
 * smartflat-power.path on the host acts on the file and removes it.
 */
final readonly class RequestFilePowerManager implements PowerManager
{
    public function __construct(private string $directory) {}

    public function perform(PowerAction $action): void
    {
        if (! is_dir($this->directory) && ! @mkdir($this->directory, 0775, true) && ! is_dir($this->directory)) {
            throw PowerException::failed($action, "теку {$this->directory} не вдалося створити");
        }

        if (! is_writable($this->directory)) {
            throw PowerException::failed($action, "тека {$this->directory} недоступна для запису");
        }

        $written = @file_put_contents(
            $this->requestPath($action),
            $action->value.' '.Carbon::now()->toIso8601String().PHP_EOL,
        );

        if ($written === false) {
            throw PowerException::failed($action, 'не вдалося записати запит для хоста');
        }
    }

    public function requestPath(PowerAction $action): string
    {
        return rtrim($this->directory, '/').'/'.$action->value.'.request';
    }
}
