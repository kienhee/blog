<?php

namespace App\Mail\Tests;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailConnection extends Mailable
{
    use Queueable, SerializesModels;

    public $full_name;
    public $environment;
    public $sentAt;
    
    /**
     * Create a new message instance.
     */
    public function __construct($email)
    {
        // Find user by email to get their name
        $user = User::where('email', $email)->first();
        
        if ($user && $user->full_name) {
            $this->full_name = $user->full_name;
        } else {
            // If user not found or no full_name, use email or a default
            $this->full_name = $email;
        }
        
        $this->environment = app()->environment();
        $this->sentAt = now()->format('d/m/Y H:i:s');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thông báo kết nối hệ thống email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tests.email-connection',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

