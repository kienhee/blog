<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\ContactReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contact $contact,
        public ContactReply $reply
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Re: {$this->contact->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.reply',
            with: [
                'contact' => $this->contact,
                'reply' => $this->reply,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
