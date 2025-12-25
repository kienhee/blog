<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:254',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'gender' => ['nullable', 'in:0,1,2'],
            'birthday' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255', 'regex:/^https:\/\/(twitter\.com|x\.com)\/.+$/'],
            'facebook_url' => ['nullable', 'url', 'max:255', 'regex:/^https:\/\/(www\.)?facebook\.com\/.+$|^https:\/\/fb\.com\/.+$/'],
            'instagram_url' => ['nullable', 'url', 'max:255', 'regex:/^https:\/\/(www\.)?instagram\.com\/.+$/'],
            'linkedin_url' => ['nullable', 'url', 'max:255', 'regex:/^https:\/\/(www\.)?linkedin\.com\/(in|company)\/.+$/'],
        ];
    }

    /**
     * Custom messages.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'full_name.min' => 'Họ tên phải có ít nhất :min ký tự.',
            'full_name.max' => 'Họ tên không được vượt quá :max ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá :max ký tự.',
            'email.unique' => 'Email đã được sử dụng.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'phone.regex' => 'Số điện thoại chỉ được chứa số.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'birthday.date' => 'Ngày sinh không hợp lệ.',
            'description.max' => 'Giới thiệu không được vượt quá :max ký tự.',
            'avatar.max' => 'Link ảnh đại diện tối đa 255 ký tự.',
            'twitter_url.url' => 'URL Twitter không hợp lệ.',
            'twitter_url.regex' => 'URL Twitter phải bắt đầu với https://twitter.com/ hoặc https://x.com/',
            'facebook_url.url' => 'URL Facebook không hợp lệ.',
            'facebook_url.regex' => 'URL Facebook phải bắt đầu với https://facebook.com/ hoặc https://fb.com/',
            'instagram_url.url' => 'URL Instagram không hợp lệ.',
            'instagram_url.regex' => 'URL Instagram phải bắt đầu với https://instagram.com/',
            'linkedin_url.url' => 'URL LinkedIn không hợp lệ.',
            'linkedin_url.regex' => 'URL LinkedIn phải bắt đầu với https://linkedin.com/in/ hoặc https://linkedin.com/company/',
        ];
    }
}
