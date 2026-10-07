<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function index()
    {
        return view('pages.experiences.index', ['experiences' => Experience::active()->get()]);
    }

    public function show(Request $request, string $locale, Experience $experience)
    {
        abort_unless($experience->is_active, 404);

        return view('pages.experiences.show', [
            'experience' => $experience->load('photos'),
            'others' => Experience::active()->whereKeyNot($experience->id)->take(3)->get(),
            'reviews' => $experience->reviews()->take(6)->get(),
            'rating' => $experience->rating(),
            'form' => ReviewController::formContext($request, experience: $experience),
        ]);
    }
}
