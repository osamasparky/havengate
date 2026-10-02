<?php

namespace App\Http\Controllers;

use App\Models\Experience;

class ExperienceController extends Controller
{
    public function index()
    {
        return view('pages.experiences.index', ['experiences' => Experience::active()->get()]);
    }

    public function show(string $locale, Experience $experience)
    {
        abort_unless($experience->is_active, 404);

        return view('pages.experiences.show', [
            'experience' => $experience->load('photos'),
            'others' => Experience::active()->whereKeyNot($experience->id)->take(3)->get(),
        ]);
    }
}
