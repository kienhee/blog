<?php

namespace App\Jobs;

use App\Mail\ContactNotificationMail;
use App\Models\Contact;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendContactNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Contact $contact
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Lấy email quản trị từ settings
            $adminEmail = Setting::getValue('email', '');

            if (empty($adminEmail)) {
                Log::warning('Admin email không được cấu hình. Không thể gửi thông báo liên hệ mới.', [
                    'contact_id' => $this->contact->id,
                ]);
                return;
            }

            // Gửi email thông báo (job đã được queue nên dùng send)
            Mail::to($adminEmail)->send(new ContactNotificationMail($this->contact));
        } catch (\Exception $e) {
            Log::error('Lỗi khi gửi email thông báo liên hệ mới: ' . $e->getMessage(), [
                'contact_id' => $this->contact->id,
                'error' => $e->getTraceAsString(),
            ]);
            // Re-throw để Laravel có thể retry job nếu cần
            throw $e;
        }
    }
}
