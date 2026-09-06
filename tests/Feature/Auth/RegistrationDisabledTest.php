<?php

use App\Models\User;
use Laravel\Fortify\Features;

it('does not register the registration feature', function () {
    expect(Features::enabled(Features::registration()))->toBeFalse()
        ->and(Features::enabled(Features::emailVerification()))->toBeFalse();
});

it('returns 404 for the registration screen', function () {
    $this->get('/register')->assertNotFound();
});

it('returns 404 when posting a registration and creates no user', function () {
    $this->post('/register', [
        'name' => 'Непроханий Гість',
        'email' => 'intruder@example.test',
        'password' => 'super-secret-password',
        'password_confirmation' => 'super-secret-password',
    ])->assertNotFound();

    expect(User::query()->where('email', 'intruder@example.test')->exists())->toBeFalse();
});

it('lets an unverified user reach the dashboard', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard'))
        ->assertOk();
});
