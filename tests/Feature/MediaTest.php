<?php

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/smartflat-media-'.bin2hex(random_bytes(6));
    $this->outside = sys_get_temp_dir().'/smartflat-outside-'.bin2hex(random_bytes(6));

    $files = new Filesystem;
    $files->makeDirectory($this->root.'/Серіали', 0777, true);
    $files->makeDirectory($this->outside, 0777, true);
    $files->put($this->root.'/Серіали/s01e01.mkv', 'episode');
    $files->put($this->root.'/film.mkv', 'film');
    $files->put($this->root.'/film.torrent', 'torrent');
    $files->put($this->outside.'/secret.txt', 'secret');

    config()->set('smartflat.media.root', $this->root);
});

afterEach(function () {
    $files = new Filesystem;
    $files->deleteDirectory($this->root);
    $files->deleteDirectory($this->outside);
});

describe('index', function () {
    it('renders the media root with directories first and without ignored extensions', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView))
            ->get(route('media.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('media/Index')
                ->where('path', '')
                ->where('parentPath', null)
                ->where('breadcrumbs', [['name' => 'Медіа', 'path' => '']])
                ->has('entries', 2)
                ->where('entries.0.name', 'Серіали')
                ->where('entries.0.isDirectory', true)
                ->where('entries.1.name', 'film.mkv')
                ->where('entries.1.size', 4)
                ->where('entries.1.sizeForHumans', '4 B')
                ->has('entries.1.modifiedAt'));
    });

    it('renders a nested directory with its parent path', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView))
            ->get(route('media.index', ['path' => 'Серіали']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('path', 'Серіали')
                ->where('parentPath', '')
                ->has('entries', 1));
    });

    it('returns 403 for a path outside the media root', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView))
            ->get(route('media.index', ['path' => '../../etc']))
            ->assertForbidden();
    });

    it('returns 403 for a user without the media.view permission', function () {
        $this->actingAs(userWithPermissions(Permission::FrameView))
            ->get(route('media.index'))
            ->assertForbidden();
    });

    it('redirects a guest to the login page', function () {
        $this->get(route('media.index'))->assertRedirect(route('login'));
    });
});

describe('destroy', function () {
    it('deletes a file and redirects back', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView, Permission::MediaDelete))
            ->from(route('media.index'))
            ->delete(route('media.destroy'), ['path' => 'film.mkv'])
            ->assertRedirect(route('media.index'));

        expect(is_file($this->root.'/film.mkv'))->toBeFalse();
    });

    it('deletes a directory with its contents', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView, Permission::MediaDelete))
            ->from(route('media.index'))
            ->delete(route('media.destroy'), ['path' => 'Серіали']);

        expect(is_dir($this->root.'/Серіали'))->toBeFalse();
    });

    it('returns 403 and keeps a file that lives outside the media root', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView, Permission::MediaDelete))
            ->delete(route('media.destroy'), ['path' => '../'.basename($this->outside).'/secret.txt'])
            ->assertForbidden();

        expect(is_file($this->outside.'/secret.txt'))->toBeTrue();
    });

    it('returns 403 for a user without the media.delete permission', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView))
            ->delete(route('media.destroy'), ['path' => 'film.mkv'])
            ->assertForbidden();

        expect(is_file($this->root.'/film.mkv'))->toBeTrue();
    });

    it('rejects a request without a path', function () {
        $this->actingAs(userWithPermissions(Permission::MediaDelete))
            ->from(route('dashboard'))
            ->delete(route('media.destroy'), [])
            ->assertSessionHasErrors(['path' => 'Вкажіть, що саме потрібно видалити.']);
    });

    it('lets an administrator delete without an explicit permission', function () {
        $this->actingAs(administrator())
            ->from(route('media.index'))
            ->delete(route('media.destroy'), ['path' => 'film.mkv']);

        expect(is_file($this->root.'/film.mkv'))->toBeFalse();
    });
});

it('shares the permissions of the signed in user with the frontend', function () {
    $user = userWithPermissions(Permission::MediaView);

    $this->actingAs($user)
        ->get(route('media.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => ['media.view'],
            ]));
});

it('shares a null user with a guest', function () {
    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user', null));
});

it('does not leak the password hash of the signed in user', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->missing('auth.user.password'));
});
