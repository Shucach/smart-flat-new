<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

describe('index', function () {
    it('lists the users with their roles', function () {
        $administrator = administrator();
        $viewer = User::factory()->create(['name' => 'Аня', 'email' => 'anya@example.test']);
        $viewer->roles()->attach(Role::factory()->create(['name' => 'viewer', 'label' => 'Спостерігач']));

        $this->actingAs($administrator)
            ->get(route('admin.users.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/users/Index')
                ->has('users.data', 2)
                ->where('users.data.0.name', 'Аня')
                ->where('users.data.0.email', 'anya@example.test')
                ->where('users.data.0.roles', [['id' => $viewer->roles->first()->id, 'name' => 'viewer', 'label' => 'Спостерігач']])
                ->has('users.data.0.createdAt')
                ->has('users.current_page'));
    });

    it('returns 403 for a user without the users.manage permission', function () {
        $this->actingAs(userWithPermissions(Permission::MediaView))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    });
});

describe('create', function () {
    it('renders the assignable roles', function () {
        $this->actingAs(administrator())
            ->get(route('admin.users.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/users/Create')
                ->has('roles', 1)
                ->has('roles.0.id')
                ->has('roles.0.name')
                ->has('roles.0.label'));
    });
});

describe('store', function () {
    it('creates a user with the selected roles', function () {
        $role = Role::factory()->withPermissions([Permission::MediaView])->create();

        $this->actingAs(administrator())
            ->post(route('admin.users.store'), [
                'name' => 'Нова Людина',
                'email' => 'new@example.test',
                'password' => 'super-secret-password',
                'password_confirmation' => 'super-secret-password',
                'roles' => [$role->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->firstWhere('email', 'new@example.test');

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Нова Людина')
            ->and($user->password)->not->toBe('super-secret-password')
            ->and($user->roles->pluck('id')->all())->toBe([$role->id])
            ->and($user->permissions())->toBe(['media.view']);
    });

    it('rejects an email that already exists', function () {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs(administrator())
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Дубль',
                'email' => 'taken@example.test',
                'password' => 'super-secret-password',
                'password_confirmation' => 'super-secret-password',
            ])
            ->assertSessionHasErrors('email');

        expect(User::query()->where('email', 'taken@example.test')->count())->toBe(1);
    });

    it('rejects an unknown role', function () {
        $this->actingAs(administrator())
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Хтось',
                'email' => 'someone@example.test',
                'password' => 'super-secret-password',
                'password_confirmation' => 'super-secret-password',
                'roles' => [999],
            ])
            ->assertSessionHasErrors('roles.0');

        expect(User::query()->where('email', 'someone@example.test')->exists())->toBeFalse();
    });

    it('returns 403 for a user without the users.manage permission', function () {
        $this->actingAs(userWithPermissions(Permission::SystemView))
            ->post(route('admin.users.store'), [
                'name' => 'Хтось',
                'email' => 'someone@example.test',
                'password' => 'super-secret-password',
                'password_confirmation' => 'super-secret-password',
            ])
            ->assertForbidden();

        expect(User::query()->where('email', 'someone@example.test')->exists())->toBeFalse();
    });
});

describe('update', function () {
    it('updates the profile and the roles without touching the password', function () {
        $user = User::factory()->create(['name' => 'Стара Назва']);
        $originalPassword = $user->password;
        $role = Role::factory()->create();

        $this->actingAs(administrator())
            ->put(route('admin.users.update', $user), [
                'name' => 'Нова Назва',
                'email' => $user->email,
                'roles' => [$role->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        expect($user->name)->toBe('Нова Назва')
            ->and($user->password)->toBe($originalPassword)
            ->and($user->roles->pluck('id')->all())->toBe([$role->id]);
    });

    it('replaces the password when a new one is given', function () {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $this->actingAs(administrator())
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'another-secret-password',
                'password_confirmation' => 'another-secret-password',
            ]);

        expect($user->refresh()->password)->not->toBe($originalPassword);
    });

    it('returns 403 for a user without the users.manage permission', function () {
        $user = User::factory()->create(['name' => 'Незмінна']);

        $this->actingAs(userWithPermissions(Permission::SystemView))
            ->put(route('admin.users.update', $user), ['name' => 'Змінена', 'email' => $user->email])
            ->assertForbidden();

        expect($user->refresh()->name)->toBe('Незмінна');
    });
});

describe('destroy', function () {
    it('deletes a user', function () {
        $user = User::factory()->create();

        $this->actingAs(administrator())
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    it('refuses to delete the signed in account', function () {
        $administrator = administrator();

        $this->actingAs($administrator)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $administrator))
            ->assertSessionHasErrors(['user' => 'Не можна видалити власний обліковий запис.']);

        $this->assertModelExists($administrator);
    });

    it('returns 403 for a user without the users.manage permission', function () {
        $user = User::factory()->create();

        $this->actingAs(userWithPermissions(Permission::SystemView))
            ->delete(route('admin.users.destroy', $user))
            ->assertForbidden();

        $this->assertModelExists($user);
    });
});
