<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Profile\ChangePasswordRequest;
use App\Http\Requests\Admin\User\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function information()
    {
        $user = Auth::user();
        return view('client.pages.profile.tabs.information', compact('user'));
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

        return view('client.pages.profile.tabs.saved-posts', compact('savedPosts'));
    }

    public function showChangePassword()
    {
        return view('client.pages.profile.tabs.change-password');
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
