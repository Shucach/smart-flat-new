<?php

it('keeps the https scheme when the tunnel forwards the protocol', function () {
    $secureUrl = preg_replace('#^https?://#', 'https://', rtrim(config('app.url'), '/'));

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.2'])
        ->get('/dashboard', ['X-Forwarded-Proto' => 'https'])
        ->assertRedirect($secureUrl.'/login');
});

it('ignores forwarded headers from an untrusted peer', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '192.168.0.127'])
        ->get('/dashboard', ['X-Forwarded-Proto' => 'https'])
        ->assertRedirect(rtrim(config('app.url'), '/').'/login');
});

it('leaves the scheme alone when nothing is forwarded', function () {
    $this->get('/dashboard')
        ->assertRedirect(rtrim(config('app.url'), '/').'/login');
});
