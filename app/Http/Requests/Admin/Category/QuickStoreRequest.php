<?php

namespace App\Http\Requests\Admin\Category;

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
            'name' => 'required|string|min:2|max:60|unique:categories,name',
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
            'name.required' => 'Vui lòng nhập tên danh mục',
            'name.string' => 'Tên danh mục phải là chuỗi ký tự',
            'name.min' => 'Tên danh mục phải có ít nhất 2 ký tự',
            'name.max' => 'Tên danh mục không được vượt quá 60 ký tự',
            'name.unique' => 'Danh mục này đã tồn tại',
        ];
    }
}

