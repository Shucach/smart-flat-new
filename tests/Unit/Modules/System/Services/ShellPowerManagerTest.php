<?php

use App\Enums\PowerAction;
use App\Modules\System\Exceptions\PowerException;
use App\Modules\System\Services\ShellPowerManager;

it('runs the command configured for the action', function () {
    $marker = sys_get_temp_dir().'/smartflat-power-'.bin2hex(random_bytes(6));

    $manager = new ShellPowerManager([
        'reboot' => "touch {$marker}",
        'shutdown' => 'true',
    ]);

    $manager->perform(PowerAction::Reboot);

    expect(file_exists($marker))->toBeTrue();

    unlink($marker);
});

it('throws when the command exits with a failure', function () {
    $manager = new ShellPowerManager(['reboot' => 'echo "no permission" >&2; exit 1']);

    expect(fn () => $manager->perform(PowerAction::Reboot))
        ->toThrow(PowerException::class, 'no permission');
});

it('throws when no command is configured for the action', function () {
    $manager = new ShellPowerManager(['reboot' => '  ']);

    expect(fn () => $manager->perform(PowerAction::Reboot))
        ->toThrow(PowerException::class, 'Команду для дії [reboot] не налаштовано.');
});
