<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\ContentBlock;
use App\Models\Experience;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Photo;
use App\Models\Promotion;
use Illuminate\Http\Response;

class SiteController extends Controller
{
    public function home()
    {
        return view('pages.home', [
            'blocks' => ContentBlock::map(),
            'stays' => Accommodation::active()->get(),
            'experiences' => Experience::active()->where('is_featured', true)->take(4)->get(),
            'facilities' => Facility::active()->take(8)->get(),
            'photos' => Photo::whereNull('photoable_id')->where('is_featured', true)->orderBy('sort_order')->take(7)->get(),
            'promotion' => Promotion::where('is_active', true)->where('show_on_site', true)
                ->where(fn ($q) => $q->whereNull('bookable_until')->orWhereDate('bookable_until', '>=', today()))
                ->latest()->first(),
            'faqs' => Faq::active()->take(6)->get(),
        ]);
    }

    public function camp()
    {
        return view('pages.camp', [
            'blocks' => ContentBlock::map(),
            'facilities' => Facility::active()->get()->groupBy('category'),
            'faqs' => Faq::active()->get()->groupBy('topic'),
        ]);
    }

    public function gallery()
    {
        $photos = Photo::orderByDesc('is_featured')->orderBy('sort_order')->get();

        return view('pages.gallery', [
            'photos' => $photos,
            'categories' => $photos->pluck('category')->unique()->values(),
        ]);
    }

    public function location()
    {
        return view('pages.location', ['blocks' => ContentBlock::map()]);
    }

    public function sitemap(): Response
    {
        $urls = [];
        foreach (array_keys(config('heavengate.locales')) as $l) {
            foreach (['home', 'stays.index', 'experiences.index', 'camp', 'gallery', 'location', 'contact'] as $r) {
                $urls[] = route($r, ['locale' => $l]);
            }
            foreach (Accommodation::active()->pluck('slug') as $s) {
                $urls[] = route('stays.show', ['locale' => $l, 'accommodation' => $s]);
            }
            foreach (Experience::active()->pluck('slug') as $s) {
                $urls[] = route('experiences.show', ['locale' => $l, 'experience' => $s]);
            }
            foreach (Page::published()->pluck('slug') as $s) {
                $urls[] = route('page', ['locale' => $l, 'page' => $s]);
            }
        }

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
