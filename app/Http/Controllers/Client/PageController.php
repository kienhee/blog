<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Repositories\NewsletterRepository;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function about()
    {
        return view('client.pages.about');
    }

    /**
     * Xử lý đăng ký newsletter
     */
    public function subscribeNewsletter(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
            ], [
                'email.required' => 'Vui lòng nhập email.',
                'email.email' => 'Email không đúng định dạng.',
                'email.max' => 'Email không được vượt quá 255 ký tự.',
            ]);

            $email = $validated['email'];

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

                // Nếu email đã tồn tại nhưng đã hủy, cập nhật lại thành active
                $existingNewsletter->update([
                    'status' => NewsletterRepository::STATUS_ACTIVE,
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cảm ơn bạn đã đăng ký lại nhận tin tức!',
                ]);
            }

            // Tạo mới newsletter subscription
            Newsletter::create([
                'email' => $email,
                'status' => NewsletterRepository::STATUS_ACTIVE,
                'subscribed_at' => now(),
            ]);

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
}
