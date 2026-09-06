<?php

it('keeps the https scheme when the reverse proxy forwards the protocol', function () {
    $secureUrl = preg_replace('#^https?://#', 'https://', rtrim(config('app.url'), '/'));

    $this->get('/dashboard', ['X-Forwarded-Proto' => 'https'])
        ->assertRedirect($secureUrl.'/login');
});

it('leaves the scheme alone when nothing is forwarded', function () {
    $this->get('/dashboard')
        ->assertRedirect(rtrim(config('app.url'), '/').'/login');
});
