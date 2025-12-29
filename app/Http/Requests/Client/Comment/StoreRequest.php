<?php

namespace App\Http\Requests\Client\Comment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'content' => ['required', 'string', 'min:3', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'post_id.required' => 'Bài viết không hợp lệ.',
            'post_id.exists' => 'Bài viết không tồn tại.',
            'content.required' => 'Vui lòng nhập nội dung bình luận.',
            'content.min' => 'Nội dung bình luận phải có ít nhất 3 ký tự.',
            'content.max' => 'Nội dung bình luận không được vượt quá 1000 ký tự.',
            'parent_id.exists' => 'Bình luận cha không tồn tại.',
        ];
    }
}
