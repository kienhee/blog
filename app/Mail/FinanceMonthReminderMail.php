<?php

namespace App\Mail;

use App\Models\FinanceMonth;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FinanceMonthReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public FinanceMonth $financeMonth
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $monthNames = [
            1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3', 4 => 'Tháng 4',
            5 => 'Tháng 5', 6 => 'Tháng 6', 7 => 'Tháng 7', 8 => 'Tháng 8',
            9 => 'Tháng 9', 10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12',
        ];
        
        $monthName = $monthNames[$this->financeMonth->month] ?? 'Tháng ' . $this->financeMonth->month;
        $year = $this->financeMonth->financeYear->year;
        
        return new Envelope(
            subject: "Nhắc nhở: Kiểm tra và khóa tài chính {$monthName}/{$year}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.finance.month-reminder',
            with: [
                'user' => $this->user,
                'financeMonth' => $this->financeMonth,
            ],
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
