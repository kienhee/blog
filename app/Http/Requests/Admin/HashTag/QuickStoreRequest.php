<?php

namespace App\Http\Requests\Admin\HashTag;

use Illuminate\Foundation\Http\FormRequest;

class QuickStoreRequest extends FormRequest
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
        return [
            'name' => 'required|string|min:2|max:20|unique:hash_tags,name',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên hashtag',
            'name.string' => 'Tên hashtag phải là chuỗi ký tự',
            'name.min' => 'Tên hashtag phải có ít nhất 2 ký tự',
            'name.max' => 'Tên hashtag không được vượt quá 20 ký tự',
            'name.unique' => 'Hashtag này đã tồn tại',
        ];
    }
}

