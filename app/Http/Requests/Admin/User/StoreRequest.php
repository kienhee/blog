<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'gender' => ['nullable', 'in:male,female,other'],
            'birthday' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255', 'regex:/^https:\/\/(twitter\.com|x\.com)\/.+$/'],
            'facebook_url' => ['nullable', 'url', 'max:255', 'regex:/^https:\/\/(www\.)?facebook\.com\/.+$|^https:\/\/fb\.com\/.+$/'],
            'instagram_url' => ['nullable', 'url', 'max:255', 'regex:/^https:\/\/(www\.)?instagram\.com\/.+$/'],
            'linkedin_url' => ['nullable', 'url', 'max:255', 'regex:/^https:\/\/(www\.)?linkedin\.com\/(in|company)\/.+$/'],
            'password' => ['required', 'string', 'min:6', 'max:255', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
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
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự',
            'phone.regex' => 'Số điện thoại chỉ được chứa số',
            'description.max' => 'Giới thiệu không được vượt quá :max ký tự',
            'twitter_url.url' => 'URL Twitter không hợp lệ',
            'twitter_url.regex' => 'URL Twitter phải bắt đầu với https://twitter.com/ hoặc https://x.com/',
            'facebook_url.url' => 'URL Facebook không hợp lệ',
            'facebook_url.regex' => 'URL Facebook phải bắt đầu với https://facebook.com/ hoặc https://fb.com/',
            'instagram_url.url' => 'URL Instagram không hợp lệ',
            'instagram_url.regex' => 'URL Instagram phải bắt đầu với https://instagram.com/',
            'linkedin_url.url' => 'URL LinkedIn không hợp lệ',
            'linkedin_url.regex' => 'URL LinkedIn phải bắt đầu với https://linkedin.com/in/ hoặc https://linkedin.com/company/',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự',
            'password.max' => 'Mật khẩu không được vượt quá :max ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
            'roles.required' => 'Vui lòng chọn ít nhất một vai trò',
            'roles.array' => 'Danh sách vai trò không hợp lệ',
            'roles.min' => 'Vui lòng chọn ít nhất một vai trò',
            'roles.*.exists' => 'Một hoặc nhiều vai trò không tồn tại',
        ];
    }
}
