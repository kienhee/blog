<?php

namespace App\Console\Commands;

use App\Mail\FinanceMonthReminderMail;
use App\Models\FinanceMonth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class SendFinanceMonthReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:send-month-reminders {--test : Test mode - gửi email cho tháng hiện tại hoặc tháng được chỉ định} {--month= : Số tháng (1-12) để test (chỉ dùng với --test)} {--year= : Năm để test (chỉ dùng với --test)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gửi email nhắc nhở kiểm tra và khóa tháng tài chính vào cuối mỗi tháng';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = \Carbon\Carbon::now();
        
        // Test mode
        if ($this->option('test')) {
            $testYear = $this->option('year') ? (int)$this->option('year') : $now->year;
            $testMonth = $this->option('month') ? (int)$this->option('month') : $now->month;
            
            $this->info("TEST MODE: Gửi email cho tháng {$testMonth}/{$testYear}...");
            
            $previousYear = $testYear;
            $previousMonthNumber = $testMonth;
        } else {
            // Lấy tháng trước (tháng vừa kết thúc)
            $previousMonth = $now->copy()->subMonth();
            $previousYear = $previousMonth->year;
            $previousMonthNumber = $previousMonth->month;
        }
        
        $this->info("Đang tìm các tháng tài chính chưa được khóa cho tháng {$previousMonthNumber}/{$previousYear}...");
        
        // Tìm tất cả các tháng của tháng trước chưa được khóa
        $unlockedMonths = FinanceMonth::whereHas('financeYear', function($query) use ($previousYear) {
                $query->where('year', $previousYear);
            })
            ->where('month', $previousMonthNumber)
            ->whereNull('locked_time')
            ->with(['user', 'financeYear'])
            ->get();
        
        if ($unlockedMonths->isEmpty()) {
            $this->info("Không có tháng nào cần gửi thông báo.");
            return SymfonyCommand::SUCCESS;
        }
        
        $this->info("Tìm thấy {$unlockedMonths->count()} tháng cần gửi thông báo.");
        
        $sentCount = 0;
        $failedCount = 0;
        
        foreach ($unlockedMonths as $financeMonth) {
            try {
                $user = $financeMonth->user;
                
                if (!$user || !$user->email) {
                    $this->warn("Tháng ID {$financeMonth->id} không có user hoặc email hợp lệ. Bỏ qua.");
                    $failedCount++;
                    continue;
                }
                
                // Gửi email
                Mail::to($user->email)->send(new FinanceMonthReminderMail($user, $financeMonth));
                
                $this->info("Đã gửi email đến {$user->email} cho tháng {$financeMonth->month}/{$financeMonth->financeYear->year}");
                $sentCount++;
                
            } catch (\Exception $e) {
                Log::error('Lỗi khi gửi email nhắc nhở tài chính', [
                    'finance_month_id' => $financeMonth->id,
                    'user_id' => $financeMonth->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $this->error("Lỗi khi gửi email cho tháng ID {$financeMonth->id}: " . $e->getMessage());
                $failedCount++;
            }
        }
        
        $this->info("Hoàn thành! Đã gửi: {$sentCount}, Lỗi: {$failedCount}");
        
        return SymfonyCommand::SUCCESS;
    }
}
