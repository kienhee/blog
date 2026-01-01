<?php

namespace App\Console\Commands\Tests;

use App\Jobs\Tests\SendScheduledTestEmail;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RunScheduledTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled test email if enabled and interval matches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if schedule test is enabled
        $enabled = Setting::getValue('schedule_test_enabled', false);
        
        if (!$enabled) {
            $this->info('Schedule test is disabled.');
            return 0;
        }

        // Get interval from settings
        $interval = (int) Setting::getValue('schedule_test_interval', 5);
        
        // Get cache key for last run time (include interval to reset when changed)
        $cacheKey = "schedule_test_last_run_{$interval}";
        $lastRunTimestamp = Cache::get($cacheKey);
        
        // If never run before or interval changed (different cache key), run now
        if (!$lastRunTimestamp) {
            $this->info("Running scheduled test for the first time (interval: {$interval} minutes)...");
            SendScheduledTestEmail::dispatch();
            Cache::put($cacheKey, now()->timestamp, now()->addHours(24));
            $this->info('Scheduled test email dispatched.');
            return 0;
        }
        
        // Calculate time difference in seconds for more accurate comparison
        $lastRunTime = \Carbon\Carbon::createFromTimestamp($lastRunTimestamp);
        $now = now();
        $intervalInSeconds = $interval * 60;
        
        // Calculate seconds since last run (ensure positive value)
        $secondsSinceLastRun = abs($now->diffInSeconds($lastRunTime));
        
        // If enough time has passed (using seconds for accuracy), run the test
        if ($secondsSinceLastRun >= $intervalInSeconds) {
            $minutesSinceLastRun = round($secondsSinceLastRun / 60, 1);
            $this->info("Running scheduled test (interval: {$interval} minutes, last run: {$minutesSinceLastRun} minutes ago)...");
            SendScheduledTestEmail::dispatch();
            Cache::put($cacheKey, $now->timestamp, now()->addHours(24));
            $this->info('Scheduled test email dispatched.');
        } else {
            $remainingSeconds = $intervalInSeconds - $secondsSinceLastRun;
            $remainingMinutes = round($remainingSeconds / 60, 1);
            $this->info("Scheduled test will run in {$remainingMinutes} minute(s).");
        }
        
        return 0;
    }
}

