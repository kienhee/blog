<?php

namespace App\Http\Requests\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'size:10', 'regex:/^(032|033|034|035|036|037|038|039|086|096|097|098|081|082|083|084|085|088|091|094|056|058|092|070|076|077|078|079|089|090|093|099|059)[0-9]{7}$/'],
            'password' => ['required', 'string', 'min:6', 'max:255', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên',
            'full_name.min' => 'Họ và tên phải có ít nhất :min ký tự',
            'full_name.max' => 'Họ và tên không được vượt quá :max ký tự',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'email.max' => 'Email không được vượt quá :max ký tự',
            'email.unique' => 'Email đã tồn tại trong hệ thống',
            'phone.size' => 'Số điện thoại phải có đúng 10 số',
            'phone.regex' => 'Số điện thoại không hợp lệ. Vui lòng nhập đúng đầu số của các nhà mạng Việt Nam',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự',
            'password.max' => 'Mật khẩu không được vượt quá :max ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
        ];
    }
}

