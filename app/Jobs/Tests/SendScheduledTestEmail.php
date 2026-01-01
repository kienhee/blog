<?php

namespace App\Jobs\Tests;

use App\Mail\Tests\ScheduledTest;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendScheduledTestEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if schedule test is enabled
        $enabled = Setting::getValue('schedule_test_enabled', false);
        
        if (!$enabled) {
            return; // Don't send if disabled
        }

        // Get interval from settings
        $interval = Setting::getValue('schedule_test_interval', 5);
        
        // Get test emails from settings
        $testEmails = Setting::getValue('test_emails', []);
        
        // If no test emails set, use admin email as fallback
        if (empty($testEmails)) {
            $adminEmail = Setting::getValue('email', config('mail.from.address'));
            if ($adminEmail) {
                $testEmails = [$adminEmail];
            } else {
                return; // No email to send to
            }
        }

        // Validate and send emails
        foreach ($testEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($email)->queue(new ScheduledTest($email, $interval));
            }
        }
    }
}

