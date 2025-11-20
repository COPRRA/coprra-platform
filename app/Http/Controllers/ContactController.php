<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

final class ContactController extends Controller
{
    /**
     * Show the contact form.
     */
    public function show(): View
    {
        return view('contact');
    }

    /**
     * Handle the contact form submission.
     */
    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Log the contact form submission
        Log::channel('daily')->info('Contact form submitted', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
        ]);

        // In a production environment, you would send an email here
        // For now, we'll just log it
        // Mail::to(config('mail.from.address'))->send(new ContactFormMail($validated));

        return back()->with('success', 'Thank you for contacting us! We will get back to you soon.');
    }
}
