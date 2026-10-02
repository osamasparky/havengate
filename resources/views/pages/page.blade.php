@extends('layouts.site')
@section('title', $page->meta_title ?: $page->title)
@section('description', $page->meta_description)

@section('content')
@include('partials.page-hero', ['title' => $page->title])
<section class="pb-28"><div class="container-hg prose-hg text-lg">{!! \Illuminate\Support\Str::of((string) $page->body)->stripTags('<p><a><strong><em><ul><ol><li><h2><h3><br><blockquote>') !!}</div></section>
@endsection
