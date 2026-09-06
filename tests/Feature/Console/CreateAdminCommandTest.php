<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an administrator and seeds the roles when they are missing', function () {
    $this->artisan('smartflat:create-admin', [
        '--email' => 'admin@example.test',
        '--name' => 'Головний',
        '--password' => 'super-secret-password',
    ])->assertSuccessful();

    $user = User::query()->firstWhere('email', 'admin@example.test');

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Головний')
        ->and(Hash::check('super-secret-password', $user->password))->toBeTrue()
        ->and($user->permissions())->toBe(Permission::values())
        ->and(Role::query()->pluck('name')->all())->toBe([Role::ADMIN, Role::VIEWER]);
});

it('updates an existing user instead of creating a second one', function () {
    $existing = User::factory()->create(['email' => 'admin@example.test', 'name' => 'Стара Назва']);

    $this->artisan('smartflat:create-admin', [
        '--email' => 'admin@example.test',
        '--name' => 'Нова Назва',
        '--password' => 'super-secret-password',
    ])->assertSuccessful();

    $this->artisan('smartflat:create-admin', [
        '--email' => 'admin@example.test',
        '--password' => 'super-secret-password',
    ])->assertSuccessful();

    expect(User::query()->where('email', 'admin@example.test')->count())->toBe(1)
        ->and($existing->refresh()->name)->toBe('Нова Назва')
        ->and($existing->roles)->toHaveCount(1)
        ->and($existing->isAdministrator())->toBeTrue();
});

it('generates and prints a password when none is given', function () {
    $this->artisan('smartflat:create-admin', ['--email' => 'admin@example.test'])
        ->expectsOutputToContain('Згенерований пароль:')
        ->assertSuccessful();

    expect(User::query()->firstWhere('email', 'admin@example.test')->password)->not->toBeEmpty();
});

it('fails for an invalid email', function () {
    $this->artisan('smartflat:create-admin', ['--email' => 'not-an-email'])->assertFailed();

    expect(User::query()->count())->toBe(0);
});
