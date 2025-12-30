<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Profile\ChangePasswordRequest;
use App\Http\Requests\Client\Profile\UpdateInformationRequest;
use App\Repositories\ProfileRepository;
use Illuminate\Support\Facades\Auth;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class ProfileController extends Controller
{
    protected $profileRepository;

    public function __construct(ProfileRepository $profileRepository)
    {
        $this->profileRepository = $profileRepository;
    }

    public function information()
    {
        $user = Auth::user();
        $seoModel = new SEOData(
            title: 'Thông tin cá nhân',
            description: 'Quản lý thông tin cá nhân, cập nhật profile và liên kết mạng xã hội.',
            robots: 'noindex, follow'
        );

        return view('client.pages.profile.tabs.information', compact('user', 'seoModel'));
    }

    public function savedPosts()
    {
        $user = Auth::user();
        $savedPosts = $this->profileRepository->getSavedPosts($user->id);

        $seoModel = new SEOData(
            title: 'Bài viết đã lưu',
            description: 'Xem lại các bài viết bạn đã lưu để đọc sau.',
            robots: 'noindex, follow'
        );

        return view('client.pages.profile.tabs.saved-posts', compact('savedPosts', 'seoModel'));
    }

    public function showChangePassword()
    {
        $seoModel = new SEOData(
            title: 'Đổi mật khẩu',
            description: 'Thay đổi mật khẩu tài khoản của bạn.',
            robots: 'noindex, nofollow'
        );

        return view('client.pages.profile.tabs.change-password', compact('seoModel'));
    }

    public function updateInformation(UpdateInformationRequest $request)
    {
        $user = Auth::user();
        $user = $this->profileRepository->updateInformation($user, $request->validated());

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
        $result = $this->profileRepository->changePassword(
            $user,
            $request->validated()['currentPassword'],
            $request->validated()['newPassword']
        );

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => $result['message'],
                    'errors' => ['currentPassword' => [$result['message']]],
                ], 422);
            }

            return redirect()
                ->route('client.profile.changePassword')
                ->withErrors(['currentPassword' => $result['message']])
                ->withInput();
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => $result['message'],
            ]);
        }

        return redirect()
            ->route('client.profile.changePassword')
            ->with('success', $result['message']);
    }
}
