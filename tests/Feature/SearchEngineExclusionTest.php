<?php

it('renders the noindex meta tags in the layout', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex, notranslate">', false)
        ->assertSee('<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex, notranslate">', false);
});

it('disallows every crawler in robots.txt', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain("User-agent: *\nDisallow: /");

    foreach (['Googlebot', 'Google-Extended', 'Bingbot', 'GPTBot', 'ClaudeBot', 'CCBot'] as $crawler) {
        expect($robots)->toContain("User-agent: {$crawler}\nDisallow: /");
    }

    expect($robots)->not->toMatch('/^\s*Allow:/m')
        ->and($robots)->not->toMatch('/^\s*Disallow:\s*$/m');
});
