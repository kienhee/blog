<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $contact = Contact::create($validated);

        // Gửi email thông báo
//        \Mail::send('emails.contact_notify', ['contact' => $contact], function ($m) use ($contact) {
//            $m->to(config('mail.from.address'))
//              ->subject('New Contact: ' . $contact->subject);
//        });

        return back()->with('success', 'Chúng tôi đã nhận được thông tin, Cảm ơn bạn đã liên hệ!');
    }
}
