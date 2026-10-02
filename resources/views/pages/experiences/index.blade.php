@extends('layouts.site')
@section('title', __('site.experiences.title'))
@section('description', __('site.experiences.intro'))

@section('content')
@include('partials.page-hero', ['eyebrow' => __('site.place'), 'title' => __('site.experiences.title'), 'intro' => __('site.experiences.intro')])
<section class="pb-28">
    <div class="container-hg grid gap-x-8 gap-y-16 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($experiences as $exp)
            <x-experience-card :experience="$exp"/>
        @endforeach
    </div>
</section>
@endsection
