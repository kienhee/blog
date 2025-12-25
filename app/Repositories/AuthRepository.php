<?php

namespace App\Repositories;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthRepository
{
    protected $model;

    /**
     * Constructor
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Gửi email đặt lại mật khẩu
     *
     * @param string $email
     * @return array
     */

    public function sendPasswordResetEmail(string $email): array
    {
        $user = $this->model->where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email này không tồn tại trong hệ thống.',
            ];
        }

        // Tạo token
        $token = Str::random(64);

        // Lưu token vào database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
        $userName = $user->full_name ?? $user->email;

        // Thêm email vào queue với template (không render sẵn để tiết kiệm database)
        try {
            Mail::to($email)->queue(
                new PasswordResetMail(
                    token: $token,
                    email: $email,
                    userName: $userName,
                    resetUrl: $resetUrl,
                    expiresIn: '60 phút'
                )
            );

            return [
                'success' => true,
                'message' => 'Chúng tôi đã gửi email hướng dẫn đặt lại mật khẩu đến địa chỉ email của bạn. Vui lòng kiểm tra hộp thư.',
            ];
        } catch (\Exception $e) {
            // Log error để debug
            Log::error('Failed to add password reset email to queue', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Kiểm tra token reset password có hợp lệ không
     *
     * @param string $token
     * @param string $email
     * @return array
     */
    public function validateResetToken(string $token, string $email): array
    {
        if (!$token || !$email) {
            return [
                'valid' => false,
                'message' => 'Liên kết đặt lại mật khẩu không hợp lệ.',
            ];
        }

        // Kiểm tra token trong database
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$passwordReset) {
            return [
                'valid' => false,
                'message' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
            ];
        }

        // Kiểm tra token có khớp không
        if (!Hash::check($token, $passwordReset->token)) {
            return [
                'valid' => false,
                'message' => 'Liên kết đặt lại mật khẩu không hợp lệ.',
            ];
        }

        // Kiểm tra token có hết hạn không (60 phút)
        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return [
                'valid' => false,
                'message' => 'Liên kết đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu lại.',
            ];
        }

        return [
            'valid' => true,
            'token' => $token,
            'email' => $email,
        ];
    }

    /**
     * Đặt lại mật khẩu
     *
     * @param string $token
     * @param string $email
     * @param string $password
     * @return array
     */
    public function resetPassword(string $token, string $email, string $password): array
    {
        // Validate token trước
        $tokenValidation = $this->validateResetToken($token, $email);
        if (!$tokenValidation['valid']) {
            return [
                'success' => false,
                'message' => $tokenValidation['message'],
            ];
        }

        // Tìm user
        $user = $this->model->where('email', $email)->first();
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy tài khoản.',
            ];
        }

        // Cập nhật mật khẩu
        $user->password = Hash::make($password);
        $user->save();

        // Xóa token đã sử dụng
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return [
            'success' => true,
            'message' => 'Mật khẩu của bạn đã được đặt lại thành công. Vui lòng đăng nhập với mật khẩu mới.',
        ];
    }
}
