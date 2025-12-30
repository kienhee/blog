<?php

namespace App\Http\Requests\Client\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = auth()->user();

        return [
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['nullable', 'string', 'size:10', 'regex:/^(032|033|034|035|036|037|038|039|086|096|097|098|081|082|083|084|085|088|091|094|056|058|092|070|076|077|078|079|089|090|093|099|059)[0-9]{7}$/'],
            'gender' => ['nullable', 'in:male,female,other'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'description' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.min' => 'Họ và tên phải có ít nhất 2 ký tự.',
            'full_name.max' => 'Họ và tên không được vượt quá 150 ký tự.',
            'phone.size' => 'Số điện thoại phải có đúng 10 số.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'birthday.date' => 'Ngày sinh không hợp lệ.',
            'birthday.before' => 'Ngày sinh phải trước ngày hiện tại.',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự.',
            'avatar.max' => 'Đường dẫn avatar không hợp lệ.',
            'twitter_url.url' => 'URL Twitter không hợp lệ.',
            'facebook_url.url' => 'URL Facebook không hợp lệ.',
            'instagram_url.url' => 'URL Instagram không hợp lệ.',
            'linkedin_url.url' => 'URL LinkedIn không hợp lệ.',
        ];
    }
}

