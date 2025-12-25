<?php

namespace App\Http\Requests\Admin\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'title' => 'required|string|min:6|max:255',
            'slug' => 'required|string|min:3|max:255|unique:posts,slug',
            'status' => 'required|in:draft,scheduled,published',
            'category_id' => 'required|exists:categories,id',
            'hashtags' => 'nullable|array',
            'hashtags.*' => 'exists:hash_tags,id',
            'content' => 'required|string|min:10',
            'thumbnail' => 'required|string',
            'allow_comment' => 'nullable|in:0,1',
            'description' => 'nullable|string|max:255',
            'scheduled_at' => ['nullable', 'date', Rule::when(fn ($input) => ! empty($input['scheduled_at']), ['after:now'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề bài viết không được để trống.',
            'title.string' => 'Tiêu đề bài viết phải là chuỗi ký tự.',
            'title.min' => 'Tiêu đề bài viết phải có ít nhất :min ký tự.',
            'title.max' => 'Tiêu đề bài viết không được vượt quá :max ký tự.',

            'slug.required' => 'Slug không được để trống.',
            'slug.string' => 'Slug phải là chuỗi ký tự.',
            'slug.min' => 'Slug phải có ít nhất :min ký tự.',
            'slug.max' => 'Slug không được vượt quá :max ký tự.',
            'slug.unique' => 'Slug này đã tồn tại, vui lòng chọn slug khác.',

            'status.required' => 'Trạng thái bài viết là bắt buộc.',
            'status.in' => 'Trạng thái bài viết không hợp lệ',

            'category_id.required' => 'Danh mục bài viết là bắt buộc.',
            'category_id.exists' => 'Danh mục được chọn không tồn tại.',

            'hashtags.array' => 'Dữ liệu hashtag phải là mảng.',
            'hashtags.*.exists' => 'Một hoặc nhiều hashtag được chọn không hợp lệ.',

            'content.required' => 'Nội dung bài viết không được để trống.',
            'content.string' => 'Nội dung bài viết phải là chuỗi ký tự.',
            'content.min' => 'Nội dung bài viết phải có ít nhất :min ký tự.',

            'thumbnail.required' => 'Ảnh thumbnail là bắt buộc.',
            'thumbnail.string' => 'Đường dẫn thumbnail phải là chuỗi ký tự.',

            'allow_comment.in' => 'Giá trị cho phép bình luận không hợp lệ (chỉ nhận 0 hoặc 1).',

            'description.string' => 'Thẻ mô tả (meta description) phải là chuỗi ký tự.',
            'description.max' => 'Thẻ mô tả (meta description) không được vượt quá 255 ký tự.',

            'scheduled_at.date' => 'Thời gian đăng bài không hợp lệ.',
            'scheduled_at.after' => 'Thời gian đăng bài phải sau thời gian hiện tại.',
        ];
    }
}
