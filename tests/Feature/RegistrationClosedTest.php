<?php

use Laravel\Fortify\Features;

it('exposes no self-registration route', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

it('keeps the fortify registration feature switched off', function () {
    expect(Features::enabled(Features::registration()))->toBeFalse();
});
