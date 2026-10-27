<?php

it('shows the account deletion page to guests', function () {
    $this->get('/usuwanie-konta')
        ->assertOk()
        ->assertSee('Usunięcie konta w aplikacji Historius')
        ->assertSee('poczta.undergroundpassion@gmail.com')
        ->assertSee('Dane, które zostaną usunięte')
        ->assertSee('Dane zachowywane i okresy przechowywania');
});
