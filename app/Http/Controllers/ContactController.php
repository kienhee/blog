<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
class ContactController extends Controller
{
    public function index()
    {
        return view('pages.admin.contact.index');
    }

    public function ajaxGetDataContact() {
        $contacts = Contact::get();
        return DataTables::of($contacts)->make(true);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $contact = Contact::create($validated);
        return back()->with('success', 'Chúng tôi đã nhận được thông tin, Cảm ơn bạn đã liên hệ!');
    }
    public function confirm($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->is_confirmed = true;
        $contact->save();

        return response()->json(['success' => true]);
    }
}
