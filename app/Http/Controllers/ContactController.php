<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    public function store(Request $request)
    {
        // Honeypot: bots fill every field.
        if ($request->filled('website')) {
            return back()->with('status', __('site.contact.sent'));
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email:rfc|max:180',
            'phone' => 'nullable|string|max:40',
            'subject' => 'nullable|string|max:160',
            'message' => 'required|string|min:10|max:4000',
        ]);

        $msg = ContactMessage::create($data + ['locale' => app()->getLocale()]);

        if ($to = setting('notification_email', config('heavengate.admin_email'))) {
            try {
                Mail::raw("From: {$msg->name} <{$msg->email}> {$msg->phone}\n\n{$msg->message}", fn ($m) => $m
                    ->to($to)->replyTo($msg->email, $msg->name)->subject('Website enquiry: '.($msg->subject ?: $msg->name)));
            } catch (\Throwable $e) {
                Log::error('Contact mail failed', ['e' => $e->getMessage()]);
            }
        }

        return back()->with('status', __('site.contact.sent'));
    }
}
