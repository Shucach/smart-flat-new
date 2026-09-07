<?php

use App\Enums\PowerAction;
use App\Modules\System\Exceptions\PowerException;
use App\Modules\System\Services\RequestFilePowerManager;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/smartflat-power-'.bin2hex(random_bytes(6));
});

afterEach(function () {
    array_map('unlink', glob($this->directory.'/*') ?: []);
    @rmdir($this->directory);
});

it('leaves a request file the host can act on', function (PowerAction $action) {
    $manager = new RequestFilePowerManager($this->directory);

    $manager->perform($action);

    expect($manager->requestPath($action))->toBeFile()
        ->and(file_get_contents($manager->requestPath($action)))->toStartWith($action->value.' ');
})->with([
    'reboot' => PowerAction::Reboot,
    'shutdown' => PowerAction::Shutdown,
]);

it('creates the directory the host watches when it is missing', function () {
    (new RequestFilePowerManager($this->directory))->perform(PowerAction::Reboot);

    expect($this->directory)->toBeDirectory();
});

it('throws when the directory cannot be written to', function () {
    mkdir($this->directory, 0o500, true);

    expect(fn () => (new RequestFilePowerManager($this->directory))->perform(PowerAction::Reboot))
        ->toThrow(PowerException::class, 'недоступна для запису');
});
