@extends('layouts.admin.master')

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <form action="" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <!-- MAIN CONTENT -->
                <div class="col-lg-8">
                    <!-- Tiêu đề -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3">
                                <input type="text" name="title" class="form-control  fw-semibold" placeholder="Nhập tiêu đề bài viết..." required>
                            </div>

                            <div class="mb-3">
                                <input type="text" name="slug" class="form-control" placeholder="duong-dan-url-than-thien" required>
                            </div>

                            <div class="mb-3">
                                <textarea name="excerpt" rows="3" class="form-control" placeholder="Tóm tắt nội dung..."></textarea>
                            </div>

                            <div class="mb-3">
                                <textarea name="content" id="editor" rows="10" class="form-control" placeholder="Nội dung bài viết..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SIDEBAR SETTINGS -->
                <div class="col-lg-4">
                    <!-- Trạng thái -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header fw-bold">Xuất bản</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="draft">Nháp</option>
                                    <option value="published">Xuất bản</option>
                                </select>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="allow_comments" id="allow_comments" checked>
                                <label class="form-check-label" for="allow_comments">
                                    Cho phép bình luận
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send-check me-1"></i>Đăng bài viết
                            </button>
                        </div>
                    </div>

                    <!-- Chuyên mục -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header fw-bold">Chuyên mục</div>
                        <div class="card-body">
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chọn chuyên mục --</option>
{{--                                @foreach($categories as $cat)--}}
{{--                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>--}}
{{--                                @endforeach--}}
                            </select>
                        </div>
                    </div>

                    <!-- Ảnh đại diện -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header fw-bold">Ảnh đại diện</div>
                        <div class="card-body">
                            <div class="input-group">
                                <input type="text" id="thumbnail" name="thumbnail" class="form-control" placeholder="Chọn ảnh..." readonly>
                                <button type="button" id="lfm" data-input="thumbnail" data-preview="holder" class="btn btn-outline-secondary">
                                    <i class="bi bi-images"></i>
                                </button>
                            </div>
                            <div id="holder" class="mt-2 text-center" style="max-height: 200px;"></div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header fw-bold">Thẻ bài viết</div>
                        <div class="card-body">
                            <input type="text" name="tags" class="form-control" placeholder="tin tức, công nghệ">
                            <small class="text-muted">Ngăn cách bằng dấu phẩy (,)</small>
                        </div>
                    </div>

                    <!-- SEO Meta -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header fw-bold">Cài đặt SEO</div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" name="meta_keywords" class="form-control" placeholder="tin tức, công nghệ">
                            </div>
                        </div>
                    </div>

                    <!-- Gợi ý SEO -->
                    <div class="card mb-3 border-info shadow-sm">
                        <div class="card-header bg-info text-white fw-bold">Gợi ý SEO</div>
                        <div class="card-body small">
                            <ul class="mb-0 ps-3">
                                <li>✔ Tiêu đề chứa từ khóa chính.</li>
                                <li>✔ Slug ngắn gọn, không dấu.</li>
                                <li>✔ Mô tả hấp dẫn, ≤ 160 ký tự.</li>
                                <li>✔ Dùng thẻ heading H2/H3 trong nội dung.</li>
                                <li>✔ ALT ảnh mang nội dung liên quan.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script src="//cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
    <script src="/vendor/laravel-filemanager/js/stand-alone-button.js"></script>
    <script>
        CKEDITOR.replace('editor', {
            filebrowserImageBrowseUrl: '/laravel-filemanager?type=Images',
            filebrowserBrowseUrl: '/laravel-filemanager?type=Files'
        });

        $('#lfm').filemanager('image');
    </script>
@endsection
