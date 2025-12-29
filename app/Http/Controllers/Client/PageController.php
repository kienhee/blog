<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Repositories\NewsletterRepository;
use Illuminate\Http\Request;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class PageController extends Controller
{
    public function about()
    {
        // SEO Data for about page
        $seoModel = new SEOData(
            title: 'Về chúng tôi',
            description: 'Tìm hiểu về chúng tôi và sứ mệnh chia sẻ kiến thức, kinh nghiệm về lập trình và công nghệ đến cộng đồng.',
            url: route('client.about', [], false),
            type: 'website',
        );

        return view('client.pages.about', compact('seoModel'));
    }

    /**
     * Xử lý đăng ký newsletter với bot detection
     */
    public function subscribeNewsletter(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
                'scroll_percentage' => 'nullable|numeric|min:0|max:100',
                'time_on_page' => 'nullable|integer|min:0',
            ], [
                'email.required' => 'Vui lòng nhập email.',
                'email.email' => 'Email không đúng định dạng.',
                'email.max' => 'Email không được vượt quá 255 ký tự.',
                'scroll_percentage.numeric' => 'Dữ liệu scroll không hợp lệ.',
                'time_on_page.integer' => 'Thời gian trên trang không hợp lệ.',
            ]);

            $email = $validated['email'];
            $scrollPercentage = $validated['scroll_percentage'] ?? 0;
            $timeOnPage = $validated['time_on_page'] ?? 0;

            // Tính toán spam score và đánh giá
            $spamScore = $this->calculateSpamScore($scrollPercentage, $timeOnPage);
            $isHuman = $spamScore < 50; // Nếu spam score < 50 thì coi là người thật
            
            // Nếu là bot/spam, đánh dấu status = 0 (inactive) hoặc có thể tạo record với status spam
            $status = $isHuman ? NewsletterRepository::STATUS_ACTIVE : NewsletterRepository::STATUS_INACTIVE;

            // Kiểm tra email đã tồn tại chưa
            $existingNewsletter = Newsletter::where('email', $email)->first();

            if ($existingNewsletter) {
                // Nếu email đã tồn tại và đang active, trả về thông báo
                if ($existingNewsletter->status === NewsletterRepository::STATUS_ACTIVE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email này đã được đăng ký trước đó.',
                    ], 422);
                }

                // Nếu email đã tồn tại nhưng đã hủy, cập nhật lại thành active (chỉ nếu là người thật)
                if ($isHuman) {
                    $existingNewsletter->update([
                        'status' => NewsletterRepository::STATUS_ACTIVE,
                        'subscribed_at' => now(),
                        'unsubscribed_at' => null,
                        'scroll_percentage' => $scrollPercentage,
                        'time_on_page' => $timeOnPage,
                        'is_human' => $isHuman,
                        'spam_score' => $spamScore,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Cảm ơn bạn đã đăng ký lại nhận tin tức!',
                    ]);
                } else {
                    // Bot/spam - vẫn lưu nhưng với status inactive
                    $existingNewsletter->update([
                        'scroll_percentage' => $scrollPercentage,
                        'time_on_page' => $timeOnPage,
                        'is_human' => $isHuman,
                        'spam_score' => $spamScore,
                    ]);
                    
                    // Trả về message giống như thành công để không tiết lộ bot detection
                    return response()->json([
                        'success' => true,
                        'message' => 'Cảm ơn bạn đã đăng ký nhận tin tức!',
                    ]);
                }
            }

            // Tạo mới newsletter subscription
            Newsletter::create([
                'email' => $email,
                'status' => $status,
                'subscribed_at' => $isHuman ? now() : null,
                'scroll_percentage' => $scrollPercentage,
                'time_on_page' => $timeOnPage,
                'is_human' => $isHuman,
                'spam_score' => $spamScore,
            ]);

            // Luôn trả về success message để không tiết lộ bot detection
            return response()->json([
                'success' => true,
                'message' => 'Cảm ơn bạn đã đăng ký nhận tin tức!',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first('email') ?? 'Dữ liệu không hợp lệ.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
            ], 500);
        }
    }

    /**
     * Tính toán spam score dựa trên hành vi người dùng
     * 
     * @param float $scrollPercentage Phần trăm scroll (0-100)
     * @param int $timeOnPage Thời gian ở trên trang (giây)
     * @return int Spam score (0-100, càng cao càng spam)
     */
    private function calculateSpamScore($scrollPercentage, $timeOnPage)
    {
        $score = 0;

        // Kiểm tra scroll percentage
        // Nếu scroll < 70% thì có khả năng là bot (form newsletter thường ở dưới)
        if ($scrollPercentage < 70) {
            $score += 40; // Bot thường không scroll xuống
        } elseif ($scrollPercentage < 50) {
            $score += 60; // Rất có khả năng là bot
        } elseif ($scrollPercentage < 30) {
            $score += 80; // Hầu như chắc chắn là bot
        }

        // Kiểm tra thời gian ở trên trang
        // Nếu time < 5 giây thì rất có khả năng là bot
        if ($timeOnPage < 5) {
            $score += 50;
        } elseif ($timeOnPage < 10) {
            $score += 30;
        } elseif ($timeOnPage < 20) {
            $score += 15;
        }

        // Nếu cả scroll < 30% và time < 5 giây thì gần như chắc chắn là bot
        if ($scrollPercentage < 30 && $timeOnPage < 5) {
            $score = 100;
        }

        // Nếu scroll >= 70% và time >= 20 giây thì giảm score (hành vi bình thường)
        if ($scrollPercentage >= 70 && $timeOnPage >= 20) {
            $score = max(0, $score - 30);
        }

        return min(100, max(0, $score));
    }
}
