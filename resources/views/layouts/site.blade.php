@php
    $locale = app()->getLocale();
    $dir = locale_dir();
    $headerTheme = trim($__env->yieldContent('header_theme')) ?: 'light';
    $pageTitle = ($title ?? null) ?: trim($__env->yieldContent('title'));
    $metaDescription = trim($__env->yieldContent('description')) ?: __('site.footer.tagline');
    $fonts = [
        'en' => 'family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Manrope:wght@400;500;600;700',
        'ar' => 'family=Amiri:ital,wght@0,400;0,700;1,400&family=IBM+Plex+Sans+Arabic:wght@400;500;600&family=Cormorant+Garamond:wght@500',
        'he' => 'family=Frank+Ruhl+Libre:wght@400;500;600&family=Assistant:wght@400;500;600;700&family=Cormorant+Garamond:wght@500',
    ][$locale];
    $nav = [
        'stays.index' => __('site.nav.stay'),
        'experiences.index' => __('site.nav.experiences'),
        'camp' => __('site.nav.camp'),
        'gallery' => __('site.nav.gallery'),
        'location' => __('site.nav.location'),
        'contact' => __('site.nav.contact'),
    ];
    $whatsapp = preg_replace('/\D/', '', (string) setting('contact_whatsapp'));
    // App-style bottom tab bar (phones & tablets).
    $tabs = [
        ['route' => 'home', 'icon' => 'home', 'label' => __('site.tabs.home'), 'active' => request()->routeIs('home')],
        ['route' => 'camp', 'icon' => 'info', 'label' => __('site.tabs.about'), 'active' => request()->routeIs('camp', 'location', 'gallery')],
        ['route' => 'book', 'icon' => 'calendar', 'label' => __('site.tabs.book'), 'active' => request()->routeIs('book', 'booking.checkout', 'booking.status', 'payments.*'), 'primary' => true],
        ['route' => 'booking.manage', 'icon' => 'user', 'label' => __('site.tabs.account'), 'active' => request()->routeIs('booking.manage*')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ __('site.brand_full') }} — {{ __('site.place') }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="theme-color" content="#0B1424">
    <link rel="canonical" href="{{ url()->current() }}">
    @foreach (array_keys(config('heavengate.locales')) as $alt)
        <link rel="alternate" hreflang="{{ $alt }}" href="{{ switch_locale_url($alt) }}">
    @endforeach
    <meta property="og:title" content="{{ $pageTitle ?: __('site.brand_full') }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('images/og.jpg') }}">
    <meta property="og:locale" content="{{ ['en' => 'en_US', 'ar' => 'ar_EG', 'he' => 'he_IL'][$locale] }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ $fonts }}&display=swap">
    <script>document.documentElement.classList.remove('no-js')</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Campground',
            'name' => 'Heaven Gate Camp',
            'url' => url('/'),
            'image' => asset('images/og.jpg'),
            'telephone' => setting('contact_phone'),
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Nuweiba–Taba Road', 'addressLocality' => 'Nuweiba', 'addressRegion' => 'South Sinai', 'postalCode' => '46621', 'addressCountry' => 'EG'],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => config('heavengate.coordinates.lat'), 'longitude' => config('heavengate.coordinates.lng')],
            'sameAs' => [setting('instagram')],
            'petsAllowed' => (bool) setting('pets_allowed', true),
            'checkinTime' => setting('check_in_time'),
            'checkoutTime' => setting('check_out_time'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @stack('head')
</head>
<body class="min-h-dvh pb-[calc(4.25rem+env(safe-area-inset-bottom))] [-webkit-tap-highlight-color:transparent] lg:pb-0">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[100] btn btn-primary">{{ __('site.skip') }}</a>

{{-- Header ----------------------------------------------------------------- --}}
<header data-header x-data="{ menu: false, lang: false }" @open-menu.window="menu = true"
        class="group/header fixed inset-x-0 top-0 z-50 transition-[background-color,box-shadow,color] duration-300
               {{ $headerTheme === 'night'
                    ? 'text-sand-50 data-[scrolled]:bg-night-900/85 data-[scrolled]:backdrop-blur-md data-[scrolled]:shadow-[0_1px_0_rgb(184_135_90/.2)]'
                    : 'bg-sand-50/85 text-ink-900 backdrop-blur-md data-[scrolled]:shadow-[0_1px_0_rgb(29_26_22/.08)]' }}">
    <div class="container-hg flex h-16 items-center justify-between gap-6 lg:h-20">
        <a href="{{ lroute('home') }}" class="h-11 shrink-0" aria-label="{{ __('site.brand_full') }}">
            <x-logo class="h-11 text-[0.95rem]"/>
        </a>

        <nav class="hidden items-center gap-8 lg:flex" aria-label="Main">
            @foreach ($nav as $route => $label)
                <a href="{{ lroute($route) }}" @class([
                    'relative text-[0.92rem] font-medium transition hover:text-copper-500',
                    'text-copper-500' => request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)),
                ])>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            {{-- Language --}}
            <div class="relative" @click.outside="lang = false">
                <button type="button" @click="lang = !lang" class="inline-flex h-10 items-center gap-1.5 rounded-full px-3 text-sm font-semibold hover:bg-current/5" aria-haspopup="true" :aria-expanded="lang" aria-label="{{ __('site.nav.language') }}">
                    <x-icon name="globe" class="size-4"/> <span class="uppercase">{{ $locale }}</span>
                </button>
                <div x-show="lang" x-cloak x-transition class="absolute end-0 top-12 w-40 overflow-hidden rounded-[var(--radius-md)] bg-white py-1 text-ink-900 shadow-[var(--shadow-lift)]">
                    @foreach (config('heavengate.locales') as $code => $l)
                        <a href="{{ switch_locale_url($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" dir="{{ $l['dir'] }}"
                           class="flex items-center justify-between px-4 py-2.5 text-sm hover:bg-sand-100 {{ $code === $locale ? 'font-semibold text-copper-600' : '' }}">
                            {{ $l['native'] }} @if ($code === $locale)<x-icon name="check" class="size-4"/>@endif
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ lroute('booking.manage') }}" class="hidden text-sm font-medium hover:text-copper-500 xl:inline">{{ __('site.nav.manage') }}</a>
            <a href="{{ lroute('book') }}" class="btn btn-primary btn-sm hidden lg:inline-flex">{{ __('site.nav.book') }}</a>
        </div>
    </div>

    {{-- Mobile menu ("More"): teleported to <body> so the header's backdrop-blur doesn't trap the fixed overlay. --}}
    <template x-teleport="body">
    <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-[60] bg-night-900 text-sand-50 lg:hidden" @keydown.escape.window="menu = false">
        <div class="container-hg flex h-16 items-center justify-between">
            <x-logo class="h-11 text-[0.95rem]"/>
            <button type="button" class="grid size-10 place-items-center" @click="menu = false" aria-label="{{ __('site.nav.close') }}"><x-icon name="x" class="size-6"/></button>
        </div>
        <nav class="container-hg mt-4 flex flex-col gap-1 overflow-y-auto pb-32" style="max-height: calc(100dvh - 4rem)">
            @foreach ($nav + ['booking.manage' => __('site.nav.manage')] as $route => $label)
                <a href="{{ lroute($route) }}" @class(['flex items-center justify-between border-b border-night-700 py-4 font-display text-3xl', 'text-copper-300' => request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route))])>
                    {{ $label }} <x-icon name="chevron" class="size-5 text-sand-200/40 rtl:-scale-x-100"/>
                </a>
            @endforeach
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach (config('heavengate.locales') as $code => $l)
                    <a href="{{ switch_locale_url($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" @class(['chip !px-4 !py-2', '!bg-copper-500 !text-night-900 !border-copper-500' => $code === $locale])>{{ $l['native'] }}</a>
                @endforeach
            </div>
            <a href="{{ lroute('book') }}" class="btn btn-primary mt-8">{{ __('site.nav.book') }}</a>
        </nav>
    </div>
    </template>
</header>

<main id="main" @class(['pt-16 lg:pt-20' => $headerTheme !== 'night'])>
    @if (session('status'))
        <div class="container-hg pt-6"><div class="panel flex items-start gap-3 border-sage-600/30 bg-sage-600/5 p-4 text-sage-600" role="status"><x-icon name="check" class="mt-0.5 size-5 shrink-0"/> {{ session('status') }}</div></div>
    @endif
    @if (session('error'))
        <div class="container-hg pt-6"><div class="panel flex items-start gap-3 border-rock-600/30 bg-rock-600/5 p-4 text-rock-600" role="alert"><x-icon name="info" class="mt-0.5 size-5 shrink-0"/> {{ session('error') }}</div></div>
    @endif

    @hasSection('content')
        @yield('content')
    @else
        {{ $slot ?? '' }}
    @endif
</main>

{{-- Footer ------------------------------------------------------------------ --}}
<footer class="night on-night relative overflow-hidden bg-night-950 pt-24 pb-10">
    <div class="container-hg">
        <div class="grid gap-14 lg:grid-cols-[1.3fr_1fr_1fr_1fr]">
            <div>
                <x-logo class="h-16 text-lg text-sand-50"/>
                <p class="mt-6 max-w-xs text-sand-200/70">{{ __('site.footer.tagline') }}</p>
                <div class="mt-6 flex gap-2">
                    <a href="{{ setting('instagram') }}" target="_blank" rel="noopener" class="grid size-11 place-items-center rounded-full border border-night-700 hover:border-copper-400 hover:text-copper-300" aria-label="Instagram"><x-icon name="instagram"/></a>
                    @if ($whatsapp)
                        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="grid size-11 place-items-center rounded-full border border-night-700 hover:border-copper-400 hover:text-copper-300" aria-label="WhatsApp"><x-icon name="whatsapp"/></a>
                    @endif
                </div>
            </div>
            <div>
                <p class="eyebrow">{{ __('site.footer.explore') }}</p>
                <ul class="mt-5 space-y-3 text-sand-200/80">
                    @foreach ($nav as $route => $label)
                        <li><a href="{{ lroute($route) }}" class="hover:text-copper-300">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="eyebrow">{{ __('site.footer.info') }}</p>
                <ul class="mt-5 space-y-3 text-sand-200/80">
                    <li><a href="{{ lroute('booking.manage') }}" class="hover:text-copper-300">{{ __('site.nav.manage') }}</a></li>
                    @foreach ($footerPages ?? [] as $p)
                        <li><a href="{{ lroute('page', ['page' => $p->slug]) }}" class="hover:text-copper-300">{{ $p->title }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="eyebrow">{{ __('site.footer.reach') }}</p>
                <ul class="mt-5 space-y-3 text-sand-200/80">
                    <li class="flex gap-3"><x-icon name="pin" class="mt-1 size-4 shrink-0 text-copper-400"/> <span>{{ setting('address') }}</span></li>
                    @if (setting('contact_phone'))<li class="flex gap-3"><x-icon name="phone" class="mt-1 size-4 shrink-0 text-copper-400"/> <a href="tel:{{ preg_replace('/\s/', '', setting('contact_phone')) }}" dir="ltr" class="hover:text-copper-300">{{ setting('contact_phone') }}</a></li>@endif
                    @if (setting('contact_email'))<li class="flex gap-3"><x-icon name="mail" class="mt-1 size-4 shrink-0 text-copper-400"/> <a href="mailto:{{ setting('contact_email') }}" class="hover:text-copper-300">{{ setting('contact_email') }}</a></li>@endif
                </ul>
            </div>
        </div>

        <div class="mt-20 flex flex-col items-start justify-between gap-4 border-t border-night-700 pt-8 text-sm text-sand-200/50 md:flex-row md:items-center">
            <p>{{ __('site.footer.rights', ['year' => now()->year]) }}</p>
            <p class="inline-flex items-center gap-2"><x-icon name="lock" class="size-4"/> {{ __('site.footer.secure') }}</p>
        </div>
    </div>
    {{-- Big quiet wordmark --}}
    <p aria-hidden="true" class="pointer-events-none mt-16 select-none text-center font-display text-[18vw] leading-[0.8] text-sand-50/[0.04]">Heaven Gate</p>
</footer>

{{-- App-style bottom tab bar (below lg). --}}
<nav aria-label="{{ __('site.tabs.label') }}" x-data
     class="fixed inset-x-0 bottom-0 z-40 border-t border-night-700/80 bg-night-900/95 text-sand-200/70 backdrop-blur-xl lg:hidden"
     style="padding-bottom: env(safe-area-inset-bottom)">
    <ul class="mx-auto grid h-[4.25rem] max-w-lg grid-cols-5 items-stretch px-1">
        @foreach (array_slice($tabs, 0, 2) as $tab)
            <li>@include('partials.tab', ['tab' => $tab])</li>
        @endforeach
        @php $book = $tabs[2]; @endphp
        <li class="relative flex justify-center">
            <a href="{{ lroute($book['route']) }}" @if ($book['active']) aria-current="page" @endif
               class="group absolute -top-5 flex flex-col items-center gap-1 text-[0.68rem] font-semibold {{ $book['active'] ? 'text-copper-300' : 'text-sand-100' }}">
                <span class="grid size-14 place-items-center rounded-full bg-copper-500 text-night-900 shadow-[0_10px_30px_-6px_rgb(184_135_90/.7)] ring-4 ring-night-900 transition group-active:scale-90">
                    <x-icon :name="$book['icon']" class="size-6"/>
                </span>
                {{ $book['label'] }}
            </a>
        </li>
        <li>@include('partials.tab', ['tab' => $tabs[3]])</li>
        <li>
            <button type="button" @click="$dispatch('open-menu')" class="group flex size-full flex-col items-center justify-center gap-1 text-[0.68rem] font-medium transition active:scale-90">
                <x-icon name="menu" class="size-[1.35rem]"/>
                {{ __('site.tabs.more') }}
            </button>
        </li>
    </ul>
</nav>

@if ($whatsapp)
    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="fixed bottom-[calc(5.5rem+env(safe-area-inset-bottom))] end-4 z-40 lg:bottom-5 lg:end-5 grid size-14 place-items-center rounded-full bg-night-900 text-copper-300 shadow-[var(--shadow-lift)] ring-1 ring-copper-300/30 transition hover:scale-105" aria-label="{{ __('site.contact.whatsapp') }}">
        <x-icon name="whatsapp" class="size-6"/>
    </a>
@endif

@livewireScripts
@stack('scripts')
</body>
</html>
