<?php

namespace App\Http\Requests\Client\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currentPassword' => ['required'],
            'newPassword' => ['required', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'currentPassword.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'newPassword.required' => 'Vui lòng nhập mật khẩu mới.',
            'newPassword.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'newPassword.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ];
    }
}

