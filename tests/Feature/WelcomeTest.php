<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('shows the landing page to a visitor', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Welcome'));
});

it('sends a signed in user straight to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
