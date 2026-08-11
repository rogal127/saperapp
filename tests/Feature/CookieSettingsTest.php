<?php

it('shows the cookie settings page to guests', function () {
    $this->get('/ustawienia-cookies')
        ->assertOk()
        ->assertSee('Ustawienia cookies')
        ->assertSee('Wyrażam zgodę')
        ->assertSee('Nie wyrażam zgody / wycofuję zgodę');
});

it('does not load Google Analytics unconditionally', function () {
    config(['services.google_analytics.id' => 'G-TEST123']);

    $response = $this->get('/ustawienia-cookies')->assertOk();

    $response->assertDontSee('<script async src="https://www.googletagmanager.com/gtag/js', false);
    $response->assertSee('analytics_consent', false);
    $response->assertSee('id="cookie-banner"', false);
});

it('hides the consent banner when analytics is not configured', function () {
    config(['services.google_analytics.id' => null]);

    $this->get('/ustawienia-cookies')
        ->assertOk()
        ->assertDontSee('id="cookie-banner"', false);
});
