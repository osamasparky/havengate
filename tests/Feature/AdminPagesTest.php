<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoBookingsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

it('renders every admin page and record in English and Arabic', function (string $locale, string $dir) {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DemoBookingsSeeder::class);
    $user = User::first();
    $user->update(['locale' => $locale]);
    $this->actingAs($user);

    $uris = collect(Route::getRoutes())
        ->filter(fn ($r) => str_starts_with($r->uri(), 'admin') && in_array('GET', $r->methods()) && ! str_contains($r->uri(), '{') && ! str_contains($r->uri(), 'password-reset'))
        ->map(fn ($r) => '/'.$r->uri());
    foreach (Filament::getResources() as $resource) {
        if ($record = $resource::getModel()::first()) {
            foreach (['view', 'edit'] as $page) {
                if ($resource::hasPage($page)) {
                    $uris->push(parse_url($resource::getUrl($page, ['record' => $record]), PHP_URL_PATH));
                }
            }
        }
    }

    foreach ($uris->unique() as $uri) {
        expect($this->get($uri)->status())->toBeLessThan(400, $uri);
    }
    $this->get('/admin/bookings')->assertSee('dir="'.$dir.'"', false);
})->with([['en', 'ltr'], ['ar', 'rtl']]);

it('switches the admin language and remembers it per user', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::first();
    $this->actingAs($user);

    $this->get('/admin/locale/ar')->assertRedirect();
    expect($user->fresh()->locale)->toBe('ar');
    $this->get('/admin/bookings')->assertSee('الحجوزات');

    $this->get('/admin/locale/en');
    $this->get('/admin/bookings')->assertSee('Bookings');
    $this->get('/admin/locale/xx')->assertNotFound();
});
