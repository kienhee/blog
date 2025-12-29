<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Profile\ChangePasswordRequest;
use App\Http\Requests\Admin\User\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class ProfileController extends Controller
{
    public function information()
    {
        $user = Auth::user();
        $seoModel = new SEOData(
            title: 'Thông tin cá nhân',
            description: 'Quản lý thông tin cá nhân, cập nhật profile và liên kết mạng xã hội.',
            robots: 'noindex, follow' // Profile pages should not be indexed
        );
        return view('client.pages.profile.tabs.information', compact('user', 'seoModel'));
    }

    public function savedPosts()
    {
        $user = Auth::user();
        
        // Lấy danh sách bài viết đã lưu
        $savedPosts = \App\Models\SavedPost::where('user_id', $user->id)
            ->with(['post' => function($query) {
                $query->where('status', 'published')
                    ->whereNull('deleted_at')
                    ->with(['user', 'category']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $seoModel = new SEOData(
            title: 'Bài viết đã lưu',
            description: 'Xem lại các bài viết bạn đã lưu để đọc sau.',
            robots: 'noindex, follow' // Profile pages should not be indexed
        );

        return view('client.pages.profile.tabs.saved-posts', compact('savedPosts', 'seoModel'));
    }

    public function showChangePassword()
    {
        $seoModel = new SEOData(
            title: 'Đổi mật khẩu',
            description: 'Thay đổi mật khẩu tài khoản của bạn.',
            robots: 'noindex, nofollow' // Password change page should not be indexed
        );
        return view('client.pages.profile.tabs.change-password', compact('seoModel'));
    }

    public function updateInformation(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        // Không cho phép thay đổi email
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email không thể thay đổi',
                ], 422);
            }
            return back()->with('error', 'Email không thể thay đổi');
        }

        // Đảm bảo email luôn là email hiện tại
        $data['email'] = $user->email;

        $user->fill($data);
        $user->save();

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thông tin thành công!',
                'user' => $user->only([
                    'full_name',
                    'email',
                    'phone',
                    'gender',
                    'birthday',
                    'description',
                    'avatar',
                    'twitter_url',
                    'facebook_url',
                    'instagram_url',
                    'linkedin_url',
                ]),
            ]);
        }

        return redirect()
            ->route('client.profile.information')
            ->with('success', 'Cập nhật thông tin thành công!');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($validated['currentPassword'], $user->password)) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mật khẩu hiện tại không chính xác.',
                    'errors' => ['currentPassword' => ['Mật khẩu hiện tại không chính xác.']],
                ], 422);
            }
            return redirect()
                ->route('client.profile.changePassword')
                ->withErrors(['currentPassword' => 'Mật khẩu hiện tại không chính xác.'])
                ->withInput();
        }

        // Cập nhật mật khẩu mới
        $user->password = Hash::make($validated['newPassword']);
        $user->save();

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Đổi mật khẩu thành công!',
            ]);
        }

        return redirect()
            ->route('client.profile.changePassword')
            ->with('success', 'Đổi mật khẩu thành công!');
    }
}
