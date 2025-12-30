<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfileRepository
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Cập nhật thông tin profile
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateInformation(User $user, array $data)
    {
        // Không cho phép thay đổi email
        if (isset($data['email']) && $data['email'] !== $user->email) {
            unset($data['email']);
        }

        // Đảm bảo email luôn là email hiện tại
        $data['email'] = $user->email;

        $user->fill($data);
        $user->save();

        return $user;
    }

    /**
     * Đổi mật khẩu
     *
     * @param User $user
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword)
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'success' => false,
                'message' => 'Mật khẩu hiện tại không chính xác.',
            ];
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return [
            'success' => true,
            'message' => 'Đổi mật khẩu thành công!',
        ];
    }

    /**
     * Lấy danh sách bài viết đã lưu
     *
     * @param int $userId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getSavedPosts($userId, $perPage = 12)
    {
        return \App\Models\SavedPost::where('user_id', $userId)
            ->with(['post' => function($query) {
                $query->where('status', 'published')
                    ->whereNull('deleted_at')
                    ->with(['user', 'category']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

