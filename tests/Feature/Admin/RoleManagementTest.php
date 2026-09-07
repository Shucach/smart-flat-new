<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

describe('index', function () {
    it('lists the roles with their permissions and grouped permission options', function () {
        $administrator = administrator();
        $role = Role::factory()->withPermissions([Permission::MediaView])->create(['label' => 'Гість']);
        User::factory()->create()->roles()->attach($role);

        $this->actingAs($administrator)
            ->get(route('admin.roles.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/roles/Index')
                ->has('roles', 2)
                ->where('roles.0.label', 'Адміністратор')
                ->where('roles.0.permissions', Permission::values())
                ->where('roles.1.label', 'Гість')
                ->where('roles.1.permissions', ['media.view'])
                ->where('roles.1.usersCount', 1)
                ->has('permissionGroups', 5)
                ->where('permissionGroups.0', [
                    'group' => 'Медіа',
                    'items' => [
                        ['value' => 'media.view', 'label' => 'Перегляд медіа'],
                        ['value' => 'media.delete', 'label' => 'Видалення медіа'],
                    ],
                ]));
    });

    it('returns 403 for a user without the users.manage permission', function () {
        $this->actingAs(userWithPermissions(Permission::SystemView))
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    });
});

describe('store', function () {
    it('creates a role with the selected permissions', function () {
        $this->actingAs(administrator())
            ->post(route('admin.roles.store'), [
                'name' => 'operator',
                'label' => 'Оператор',
                'permissions' => ['media.view', 'frame.upload'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::query()->firstWhere('name', 'operator');

        expect($role)->not->toBeNull()
            ->and($role->label)->toBe('Оператор')
            ->and($role->permissions)->toBe(['media.view', 'frame.upload']);
    });

    it('rejects an unknown permission', function () {
        $this->actingAs(administrator())
            ->from(route('admin.roles.index'))
            ->post(route('admin.roles.store'), [
                'name' => 'operator',
                'label' => 'Оператор',
                'permissions' => ['media.destroy-everything'],
            ])
            ->assertSessionHasErrors('permissions.0');

        expect(Role::query()->where('name', 'operator')->exists())->toBeFalse();
    });

    it('rejects a system name that is not a slug', function () {
        $this->actingAs(administrator())
            ->from(route('admin.roles.index'))
            ->post(route('admin.roles.store'), ['name' => 'Оператор 1', 'label' => 'Оператор'])
            ->assertSessionHasErrors(['name' => 'Системна назва може містити лише латиницю, цифри, дефіс та підкреслення.']);
    });

    it('rejects a duplicate system name', function () {
        Role::factory()->create(['name' => 'operator']);

        $this->actingAs(administrator())
            ->from(route('admin.roles.index'))
            ->post(route('admin.roles.store'), ['name' => 'operator', 'label' => 'Інший оператор'])
            ->assertSessionHasErrors('name');

        expect(Role::query()->where('name', 'operator')->count())->toBe(1);
    });

    it('returns 403 for a user without the users.manage permission', function () {
        $this->actingAs(userWithPermissions(Permission::SystemView))
            ->post(route('admin.roles.store'), ['name' => 'operator', 'label' => 'Оператор'])
            ->assertForbidden();

        expect(Role::query()->where('name', 'operator')->exists())->toBeFalse();
    });
});

describe('update', function () {
    it('replaces the label and the permissions of a role', function () {
        $role = Role::factory()->withPermissions([Permission::MediaView])->create();

        $this->actingAs(administrator())
            ->put(route('admin.roles.update', $role), [
                'label' => 'Оновлена роль',
                'permissions' => ['frame.view'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role->refresh();

        expect($role->label)->toBe('Оновлена роль')
            ->and($role->permissions)->toBe(['frame.view']);
    });

    it('keeps every permission on the super role', function () {
        $administrator = administrator();
        $role = $administrator->roles->first();

        $this->actingAs($administrator)
            ->put(route('admin.roles.update', $role), ['label' => 'Головний', 'permissions' => []]);

        expect($role->refresh()->permissions)->toBe(Permission::values());
    });
});

describe('destroy', function () {
    it('deletes a role and detaches it from its users', function () {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs(administrator())
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('role_user', ['role_id' => $role->id]);
        expect($user->refresh()->permissions())->toBe([]);
    });

    it('refuses to delete the super role', function () {
        $administrator = administrator();
        $role = $administrator->roles->first();

        $this->actingAs($administrator)
            ->from(route('admin.roles.index'))
            ->delete(route('admin.roles.destroy', $role))
            ->assertSessionHasErrors(['role' => 'Роль адміністратора видалити не можна.']);

        $this->assertModelExists($role);
    });

    it('returns 403 for a user without the users.manage permission', function () {
        $role = Role::factory()->create();

        $this->actingAs(userWithPermissions(Permission::SystemView))
            ->delete(route('admin.roles.destroy', $role))
            ->assertForbidden();

        $this->assertModelExists($role);
    });
});
