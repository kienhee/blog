<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Tests\EmailConnection;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    public function index()
    {
        $defaults = [
            'site_name' => '',
            'site_description' => '',
            'facebook' => '',
            'youtube' => '',
            'twitter' => '',
            'instagram' => '',
            'tiktok' => '',
            'linkedin' => '',
            'telegram' => '',
            'pinterest' => '',
            'github' => '',
            'address' => '',
            'email' => '',
            'phone' => '',
            'map' => '',
            'posts_per_page' => 15,
            'schedule_test_enabled' => false,
            'schedule_test_interval' => 5,
            'test_emails' => [],
        ];

        $settings = [];
        foreach ($defaults as $key => $default) {
            $settings[$key] = Setting::getValue($key, $default);
        }

        // Get users with emails for select2
        $users = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->select('id', 'email', 'full_name')
            ->orderBy('email')
            ->get();

        return view('admin.modules.settings.index', compact('settings', 'users'));
    }

    public function update(Request $request)
    {
        $payloads = [
            'site_name' => $request->site_name,
            'site_description' => $request->site_description,
            'facebook' => $request->facebook,
            'youtube' => $request->youtube,
            'twitter' => $request->twitter,
            'instagram' => $request->instagram,
            'tiktok' => $request->tiktok,
            'linkedin' => $request->linkedin,
            'telegram' => $request->telegram,
            'pinterest' => $request->pinterest,
            'github' => $request->github,
            'address' => $request->address,
            'email' => $request->email,
            'phone' => $request->phone,
            'map' => $request->map,
            'posts_per_page' => (int) $request->posts_per_page,
            'schedule_test_enabled' => $request->has('schedule_test_enabled'),
            'schedule_test_interval' => (int) ($request->schedule_test_interval ?? 5),
            'test_emails' => $request->test_emails ?? [],
        ];

        foreach ($payloads as $key => $value) {
            Setting::setValue($key, $value);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật cài đặt thành công!'
            ]);
        }

        return redirect()->back()->with('success', 'Cập nhật cài đặt thành công!');
    }

    public function testEmailSetup()
    {
        $email = Auth::user()->email;
        Mail::to($email)->queue(new EmailConnection($email));

        return response()->json([
            'message' => 'Kiểm tra kết nối gửi mail thành công'
        ]);
    }

    public function testQueue()
    {
        try {
            // Get test emails from settings
            $testEmails = Setting::getValue('test_emails', []);
            
            if (empty($testEmails)) {
                return response()->json([
                    'message' => 'Vui lòng chọn ít nhất một email để kiểm tra.'
                ], 400);
            }

            // Validate emails
            $validEmails = [];
            foreach ($testEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $validEmails[] = $email;
                }
            }

            if (empty($validEmails)) {
                return response()->json([
                    'message' => 'Không có email hợp lệ nào được chọn.'
                ], 400);
            }

            // Send email to all selected emails
            foreach ($validEmails as $email) {
                Mail::to($email)->queue(new EmailConnection($email));
            }

            return response()->json([
                'message' => 'Kiểm tra kết nối queue thành công. Email đã được thêm vào queue cho ' . count($validEmails) . ' địa chỉ email.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi kiểm tra queue: ' . $e->getMessage()
            ], 500);
        }
    }
}
