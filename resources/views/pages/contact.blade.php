@extends('layouts.site')
@section('title', __('site.contact.title'))
@php $whatsapp = preg_replace('/\D/', '', (string) setting('contact_whatsapp')); @endphp

@section('content')
@include('partials.page-hero', ['eyebrow' => __('site.place'), 'title' => __('site.contact.title'), 'intro' => __('site.contact.intro')])

<section class="pb-28">
    <div class="container-hg grid gap-16 lg:grid-cols-[1fr_1.3fr]">
        <div class="space-y-4">
            @if ($whatsapp)
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="panel flex items-center gap-5 p-6 transition hover:border-copper-500 reveal">
                    <span class="grid size-12 place-items-center rounded-full bg-night-900 text-copper-300"><x-icon name="whatsapp"/></span>
                    <span><span class="block font-display text-xl">{{ __('site.contact.whatsapp') }}</span><span class="text-sm text-ink-600" dir="ltr">{{ setting('contact_whatsapp') }}</span></span>
                </a>
            @endif
            @if (setting('contact_phone'))
                <a href="tel:{{ preg_replace('/\s/', '', setting('contact_phone')) }}" class="panel flex items-center gap-5 p-6 transition hover:border-copper-500 reveal">
                    <span class="grid size-12 place-items-center rounded-full bg-sand-100 text-copper-600"><x-icon name="phone"/></span>
                    <span><span class="block font-display text-xl">{{ __('site.contact.call') }}</span><span class="text-sm text-ink-600" dir="ltr">{{ setting('contact_phone') }}</span></span>
                </a>
            @endif
            @if (setting('contact_email'))
                <a href="mailto:{{ setting('contact_email') }}" class="panel flex items-center gap-5 p-6 transition hover:border-copper-500 reveal">
                    <span class="grid size-12 place-items-center rounded-full bg-sand-100 text-copper-600"><x-icon name="mail"/></span>
                    <span><span class="block font-display text-xl">{{ __('site.contact.write') }}</span><span class="text-sm text-ink-600">{{ setting('contact_email') }}</span></span>
                </a>
            @endif
            <a href="{{ setting('instagram') }}" target="_blank" rel="noopener" class="panel flex items-center gap-5 p-6 transition hover:border-copper-500 reveal">
                <span class="grid size-12 place-items-center rounded-full bg-sand-100 text-copper-600"><x-icon name="instagram"/></span>
                <span><span class="block font-display text-xl">{{ __('site.contact.follow') }}</span><span class="text-sm text-ink-600">@heavengatecamp</span></span>
            </a>
        </div>

        <form method="POST" action="{{ lroute('contact.store') }}" class="panel grid gap-5 p-7 md:grid-cols-2 md:p-10 reveal">
            @csrf
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            @foreach (['name' => 'text', 'email' => 'email', 'phone' => 'tel', 'subject' => 'text'] as $field => $type)
                <div>
                    <label for="c-{{ $field }}" class="field-label">{{ __('site.contact.'.$field) }}</label>
                    <input id="c-{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}" class="field" @required(in_array($field, ['name', 'email'])) @if($field === 'phone') dir="ltr" @endif>
                    @error($field)<p class="field-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div class="md:col-span-2">
                <label for="c-message" class="field-label">{{ __('site.contact.message') }}</label>
                <textarea id="c-message" name="message" rows="6" class="field" required>{{ old('message') }}</textarea>
                @error('message')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2"><button class="btn btn-primary">{{ __('site.contact.send') }} <x-icon name="arrow" class="size-4"/></button></div>
        </form>
    </div>
</section>
@endsection
