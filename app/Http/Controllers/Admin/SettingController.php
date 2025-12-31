<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmailConnection;
use App\Models\Setting;
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
            'home_categories' => [],
        ];

        $settings = [];
        foreach ($defaults as $key => $default) {
            $settings[$key] = Setting::getValue($key, $default);
        }

        $categories = [];

        return view('admin.modules.settings.index', compact('settings', 'categories'));
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
            'home_categories' => $request->home_categories ?? [],
        ];

        foreach ($payloads as $key => $value) {
            Setting::setValue($key, $value);
        }

        return redirect()->back()->with('success', 'Cập nhật cài đặt thành công!');
    }

    public function testEmailSetup()
    {

        Mail::to(Auth::user()->email)->queue(new EmailConnection());

        return response()->json([
            'message' => 'Kiểm tra kết nối gửi mail thành công'
        ]);
    }
}
