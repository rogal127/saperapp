<?php

it('shows the privacy policy as an html page to guests', function () {
    $this->get('/polityka')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=utf-8')
        ->assertSee('Polityka prywatności serwisu historius.pl')
        ->assertSee('Prospekcja Artur Troncik')
        ->assertSee('Odbiorcy danych')
        ->assertSee('Pliki cookies i podobne technologie');
});
