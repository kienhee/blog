<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfileRepository
{
    public function __construct(User $model)
    {
        // Model được inject để đảm bảo DI container hoạt động đúng
        // Nhưng không được sử dụng vì các methods nhận User object trực tiếp
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
     * @param int|null $perPage Nếu null, sử dụng giá trị từ settings
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getSavedPosts($userId, $perPage = null)
    {
        if ($perPage === null) {
            $perPage = get_posts_per_page();
        }
        
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

