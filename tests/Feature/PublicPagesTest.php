<?php

use Database\Seeders\DatabaseSeeder;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('redirects root to a locale', function () {
    $this->get('/')->assertRedirect();
});

it('renders public pages in every language with the right direction', function (string $locale, string $dir) {
    foreach (['', '/stay', '/stay/sea-view-chalet', '/experiences', '/camp', '/gallery', '/location', '/contact', '/book', '/manage'] as $path) {
        $this->get("/{$locale}{$path}")->assertOk()->assertSee('dir="'.$dir.'"', false);
    }
})->with([['en', 'ltr'], ['ar', 'rtl'], ['he', 'rtl']]);

it('serves calendar availability JSON', function () {
    $this->getJson('/api/availability/sea-view-chalet')->assertOk()->assertJsonStructure(['currency', 'days']);
});

it('404s unknown locales', function () {
    $this->get('/fr')->assertNotFound();
});
