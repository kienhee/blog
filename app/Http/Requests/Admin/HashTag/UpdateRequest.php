<?php

namespace App\Http\Requests\Admin\HashTag;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
        $id = $this->route('id');

        return [
            'name' => 'required|string|min:2|max:20',
            'slug' => 'required|string|min:2|max:20|unique:hash_tags,slug,'.$id,
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
            // Tên hashtag
            'name.required' => 'Tên hashtag là bắt buộc.',
            'name.string' => 'Tên hashtag phải là chuỗi ký tự.',
            'name.min' => 'Tên hashtag phải có ít nhất :min ký tự.',
            'name.max' => 'Tên hashtag không được vượt quá :max ký tự.',

            // Slug
            'slug.required' => 'Slug là bắt buộc.',
            'slug.string' => 'Slug phải là chuỗi ký tự hợp lệ.',
            'slug.min' => 'Slug phải có ít nhất :min ký tự.',
            'slug.max' => 'Slug không được vượt quá :max ký tự.',
            'slug.unique' => 'Slug này đã tồn tại, vui lòng chọn slug khác.',
        ];
    }
}
