<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'name' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255|unique:categories,slug',
            'description' => 'nullable|string|min:3|max:255',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'thumbnail' => 'nullable|string',
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
            // Tên danh mục
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.string' => 'Tên danh mục phải là chuỗi ký tự.',
            'name.min' => 'Tên danh mục phải có ít nhất :min ký tự.',
            'name.max' => 'Tên danh mục không được vượt quá :max ký tự.',

            // Slug
            'slug.required' => 'Slug là bắt buộc.',
            'slug.string' => 'Slug phải là chuỗi ký tự hợp lệ.',
            'slug.min' => 'Slug phải có ít nhất :min ký tự.',
            'slug.max' => 'Slug không được vượt quá :max ký tự.',
            'slug.unique' => 'Slug này đã tồn tại, vui lòng chọn slug khác.',

            // Mô tả
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.min' => 'Mô tả phải có ít nhất :min ký tự.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',
            // Danh mục cha
            'parent_id.integer' => 'ID danh mục cha phải là số nguyên.',
            'parent_id.exists' => 'Danh mục cha không tồn tại trong hệ thống.',
        ];
    }
}
