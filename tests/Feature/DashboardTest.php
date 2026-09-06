<?php

use App\Enums\Permission;
use App\Models\User;
use App\Modules\Frame\Contracts\FrameGateway;
use App\Modules\Frame\Services\NullFrameGateway;
use Illuminate\Filesystem\Filesystem;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/smartflat-dashboard-'.bin2hex(random_bytes(6));

    $files = new Filesystem;
    $files->makeDirectory($this->root.'/Серіали', 0777, true);
    $files->put($this->root.'/film.mkv', 'film');

    config()->set('smartflat.media.root', $this->root);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->root);
});

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('renders every block for an administrator', function () {
    $this->app->instance(FrameGateway::class, new NullFrameGateway(['a.jpg', 'b.jpg']));

    $this->actingAs(administrator())
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('snapshot.cpu')
            ->where('counters.media', ['directories' => 1, 'files' => 1])
            ->where('counters.frame', ['total' => 2])
            ->where('counters.users', ['total' => 1, 'roles' => 1]));
});

it('omits the blocks the user has no permission for', function () {
    $this->actingAs(userWithPermissions(Permission::MediaView))
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('snapshot', null)
            ->where('counters.media', ['directories' => 1, 'files' => 1])
            ->where('counters.frame', null)
            ->where('counters.users', null));
});

it('renders an empty dashboard for a user without any permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('snapshot', null)
            ->where('counters', ['media' => null, 'frame' => null, 'users' => null]));
});
