<?php

use App\Enums\Permission;
use App\Enums\PowerAction;
use App\Modules\System\Contracts\PowerManager;
use App\Modules\System\Exceptions\PowerException;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\mock;

describe('index', function () {
    it('renders the snapshot of the host', function () {
        $this->actingAs(userWithPermissions(Permission::SystemView))
            ->get(route('system.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('system/Index')
                ->where('canPower', false)
                ->has('snapshot.host', fn (AssertableInertia $host) => $host
                    ->has('name')->has('os')->has('uptimeSeconds')->has('bootedAt'))
                ->has('snapshot.cpu', fn (AssertableInertia $cpu) => $cpu
                    ->has('usagePercent')->has('cores')->has('loadAverage', 3)->has('temperatureCelsius'))
                ->has('snapshot.memory', fn (AssertableInertia $memory) => $memory
                    ->has('totalBytes')->has('usedBytes')->has('freeBytes')->has('usagePercent')
                    ->has('swapTotalBytes')->has('swapUsedBytes'))
                ->has('snapshot.disks.0', fn (AssertableInertia $disk) => $disk
                    ->has('device')->has('mountPoint')->has('label')
                    ->has('totalBytes')->has('usedBytes')->has('freeBytes')->has('usagePercent'))
                ->has('snapshot.capturedAt'));
    });

    it('tells a user with the system.power permission that power actions are available', function () {
        $this->actingAs(userWithPermissions(Permission::SystemView, Permission::SystemPower))
            ->get(route('system.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canPower', true));
    });

    it('returns 403 for a user without the system.view permission', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView))
            ->get(route('system.index'))
            ->assertForbidden();
    });
});

describe('power', function () {
    it('performs the requested action on the host', function (PowerAction $action) {
        mock(PowerManager::class)->shouldReceive('perform')->once()->with($action);

        $this->actingAs(userWithPermissions(Permission::SystemView, Permission::SystemPower))
            ->from(route('system.index'))
            ->post(route('system.power'), ['action' => $action->value])
            ->assertRedirect(route('system.index'))
            ->assertSessionHasNoErrors();
    })->with([
        'reboot' => PowerAction::Reboot,
        'shutdown' => PowerAction::Shutdown,
    ]);

    it('returns 403 for a user without the system.power permission', function () {
        mock(PowerManager::class)->shouldNotReceive('perform');

        $this->actingAs(userWithPermissions(Permission::SystemView))
            ->post(route('system.power'), ['action' => 'reboot'])
            ->assertForbidden();
    });

    it('rejects an unknown action', function () {
        mock(PowerManager::class)->shouldNotReceive('perform');

        $this->actingAs(userWithPermissions(Permission::SystemPower))
            ->from(route('dashboard'))
            ->post(route('system.power'), ['action' => 'explode'])
            ->assertSessionHasErrors('action');
    });

    it('reports a failing power command back to the user', function () {
        mock(PowerManager::class)
            ->shouldReceive('perform')
            ->andThrow(PowerException::failed(PowerAction::Reboot, 'немає прав'));

        $this->actingAs(userWithPermissions(Permission::SystemPower))
            ->from(route('dashboard'))
            ->post(route('system.power'), ['action' => 'reboot'])
            ->assertSessionHasErrors('action');
    });
});
