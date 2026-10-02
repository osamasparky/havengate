@php if (! in_array(app()->getLocale(), array_keys(config('heavengate.locales')))) app()->setLocale('en'); @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>404 · Heaven Gate Camp</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500&family=Manrope:wght@400;600&display=swap">
@vite(['resources/css/app.css'])</head>
<body class="night on-night grid min-h-dvh place-items-center p-6 text-center">
    <div>
        <x-logo mark class="mx-auto h-28"/>
        <h1 class="t-h1 mt-10">{{ __('site.errors.404_title') }}</h1>
        <p class="lede mx-auto mt-4">{{ __('site.errors.404_body') }}</p>
        <a href="{{ url('/') }}" class="btn btn-primary mt-10">{{ __('site.errors.home') }}</a>
    </div>
</body>
</html>
