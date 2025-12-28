<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Common\ChangePasswordRequest;
use App\Http\Requests\Client\Profile\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function profile()
    {
        $user = Auth::user()->load('roles');
        return view('client.pages.profile.index', compact('user'));
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

        return view('client.pages.profile.saved-posts.index', compact('savedPosts'));
    }

    public function showChangePassword()
    {
        return view('client.pages.profile.change-password.index');
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        // Kiểm tra email đã verified thì không cho phép thay đổi
        if ($user->email_verified_at && isset($data['email']) && $data['email'] !== $user->email) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email đã được xác thực không thể thay đổi',
                ], 422);
            }
            return back()->with('error', 'Email đã được xác thực không thể thay đổi');
        }

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
            ->route('client.profile.index')
            ->with('success', 'Cập nhật thông tin thành công!');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($validated['currentPassword'], $user->password)) {
            return redirect()
                ->route('client.profile.changePassword')
                ->withErrors(['currentPassword' => 'Mật khẩu hiện tại không chính xác.'])
                ->withInput();
        }

        // Cập nhật mật khẩu mới
        $user->password = Hash::make($validated['newPassword']);
        $user->save();

        return redirect()
            ->route('client.profile.changePassword')
            ->with('success', 'Đổi mật khẩu thành công!');
    }
}
