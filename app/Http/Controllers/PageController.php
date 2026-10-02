<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show(string $locale, Page $page)
    {
        abort_unless($page->is_published, 404);

        return view('pages.page', ['page' => $page]);
    }
}
