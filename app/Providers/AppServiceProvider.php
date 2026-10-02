<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Models\Page;
use App\Services\Payments\EasyKashGateway;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EasyKashGateway::class, fn () => new EasyKashGateway(config('services.easykash')));
    }

    public function boot(): void
    {
        // Re-apply the locale on Livewire update requests (they hit /livewire/update, not /{locale}/...).
        Livewire::addPersistentMiddleware([SetLocale::class]);

        View::composer('layouts.site', function ($view) {
            static $footerPages;
            try {
                $footerPages ??= Page::published()->where('show_in_footer', true)->get(['slug', 'title']);
            } catch (\Throwable) {
                $footerPages = collect();
            }
            $view->with('footerPages', $footerPages);
        });
    }
}
