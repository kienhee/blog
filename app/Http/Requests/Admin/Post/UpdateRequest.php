<?php

namespace App\Http\Requests\Admin\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'title' => 'required|string|min:6|max:255',
            'slug' => 'required|string|min:3|max:255|unique:posts,slug,'.$id,
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

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'title.min' => 'Tiêu đề phải có ít nhất :min ký tự.',
            'title.max' => 'Tiêu đề không được vượt quá :max ký tự.',
            'slug.required' => 'Vui lòng nhập slug cho bài viết.',
            'slug.min' => 'Slug phải có ít nhất :min ký tự.',
            'slug.max' => 'Slug không được vượt quá :max ký tự.',
            'slug.unique' => 'Slug này đã tồn tại, vui lòng chọn slug khác.',
            'status.required' => 'Vui lòng chọn trạng thái bài viết.',
            'status.in' => 'Trạng thái không hợp lệ',
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'hashtags.array' => 'Hashtag phải là dạng danh sách.',
            'hashtags.*.exists' => 'Hashtag không hợp lệ.',
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
            'content.min' => 'Nội dung bài viết phải có ít nhất :min ký tự.',
            'thumbnail.required' => 'Vui lòng chọn ảnh thumbnail.',
            'allow_comment.in' => 'Giá trị cho phép bình luận không hợp lệ (chỉ nhận 0 hoặc 1).',
            'description.string' => 'Meta description phải là chuỗi.',
            'description.max' => 'Meta description không được vượt quá 255 ký tự.',

            'scheduled_at.date' => 'Thời gian đăng bài không hợp lệ.',
            'scheduled_at.after' => 'Thời gian đăng bài phải sau thời gian hiện tại.',
        ];
    }
}
