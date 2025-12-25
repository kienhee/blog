@extends('admin.layouts.master')
@section('title', 'Thêm mới bài viết')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    @include('admin.layouts.sections.tinymce-config')
@endpush
@section('content')
    <section>
        <form id="form_blog" action="{{ route('admin.posts.store') }}" method="POST">
            @csrf
            @include('admin.components.headingPage', [
                'description' => 'Viết bài mới cho blog hoặc trang tin tức',
                'button' => 'create',
                'listLink' => 'admin.posts.list',
            ])
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Thông tin bài viết</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="inputSlug">Tiêu đề bài viết <span class="text-muted">(Nên
                                        dưới 60 ký tự để tối ưu SEO)</span></label>
                                <input type="text" class="form-control" id="inputSlug" placeholder="Nhập tiêu đề..."
                                    name="title" value="{{ old('title') }}" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="outputSlug">Slug</label>
                                <input type="text" class="form-control" id="outputSlug" placeholder="" name="slug"
                                    value="{{ old('slug') }}" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="description">Meta Description <span
                                        class="text-muted">(Optional - Nên dưới 150 ký tự để tối ưu SEO)</span></label>
                                <textarea class="form-control" id="description" name="description" rows="2"
                                    placeholder="Nhập meta description...">{{ old('description') }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nội dung</label>
                                <!-- Loading Overlay -->
                                <div id="editor-loading"
                                    class="d-flex flex-column justify-content-center align-items-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-3">Loading editor...</p>
                                </div>
                                <!-- Textarea (hidden until TinyMCE is ready) -->
                                <textarea id="editor" name="content" class="d-none">{{ old('content') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0" id="upload-avatar">Ảnh bìa</h5>
                            <small class="text-muted">Format: 1920 x 1080 - 16:9</small>
                        </div>
                        <div class="card-body">
                            <div id="upload_box">
                                <div class="my-5 d-flex justify-content-center align-items-center">
                                    <button class="btn bg-label-primary upload_btn">Chọn ảnh từ máy</button>
                                </div>
                            </div>
                            <div class="fallback">
                                <input id="thumbnail" name="thumbnail" type="hidden" value="{{ old('thumbnail') }}"
                                    required />
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Cài đặt bài viết</h5>
                        </div>

                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="status">Trạng thái</label>
                                <select id="status" class="select2 form-select" name="status">
                                    @foreach ($statusLabels as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status') == $status)>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 select2-primary">
                                <label class="form-label" for="category_id">Danh mục</label>
                                <div class="d-flex align-items-center mb-2 gap-2 ">
                                    <div class="w-100">
                                        <select id="category_id" name="category_id" class="select2 form-select"
                                            data-allow-clear="true">
                                            <option value="">Vui lòng chọn</option>
                                            {!! App\Models\Category::renderOptions($categories, old('category_id')) !!}
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#addCategoryModal">
                                        <i class="bx bx-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3 select2-danger">
                                <label class="form-label" for="hashtag">Hashtags</label>
                                <div class="d-flex align-items-center mb-2 gap-2 ">
                                    <div class="w-100"> <select id="hashtag" class="select2 form-select" multiple
                                            data-allow-clear="true" name="hashtags[]">
                                            @foreach ($hashtags as $item)
                                                <option value="{{ $item->id }}" @selected(in_array($item->id, old('hashtags', [])))>
                                                    {{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#addHashtagModal">
                                        <i class="bx bx-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input type="hidden" name="allow_comment" value="0">
                                <input class="form-check-input" type="checkbox" id="allow_comment" name="allow_comment"
                                    value="1" @checked(old('allow_comment', true)) />
                                <label class="form-check-label" for="allow_comment">Cho phép bình luận</label>
                            </div>
                            <div class="mb-3" id="scheduled_at_field" style="display: none;">
                                <label class="form-label" for="scheduled_at">Đăng bài theo lịch <span
                                        class="text-muted">(Bắt buộc)</span></label>
                                <input type="text" class="form-control" id="scheduled_at" name="scheduled_at"
                                    value="{{ old('scheduled_at') }}" placeholder="Chọn ngày và giờ đăng bài" />
                                <small class="text-muted">Chọn thời gian đăng bài tự động. Bài viết sẽ tự động đăng vào
                                    thời gian đã chọn.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <!-- Add Hashtag Modal -->
    <div class="modal fade" id="addHashtagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Hashtag mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addHashtagForm">
                        <div class="mb-3">
                            <label class="form-label" for="hashtag_name">Tên Hashtag</label>
                            <input type="text" class="form-control" id="hashtag_name" name="name" maxlength="20"
                                required />
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="saveHashtag">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm Danh mục mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm">
                        <div class="mb-3">
                            <label class="form-label" for="category_name">Tên Danh mục</label>
                            <input type="text" class="form-control" id="category_name" name="name" required />
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="saveCategory">Lưu</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script>
        // Define routes for post form
        window.categoryQuickStoreUrl = "{{ route('admin.categories.quickStore') }}";
        window.hashtagQuickStoreUrl = "{{ route('admin.hashtags.quickStore') }}";
        window.hashtagSearchUrl = "{{ route('admin.hashtags.search') }}";
    </script>
    @vite([
        // -------Common -------
        'resources/js/admin/common/forms/generate-slug.js',
        'resources/js/admin/common/uploads/upload-image-alone.js',
        'resources/js/admin/common/forms/forms-selects.js',
        // -------Pages -------
        'resources/js/admin/pages/post/form.js',
        'resources/js/admin/pages/post/quick-category.js',
        'resources/js/admin/pages/post/quick-hashtag.js',
    ])
@endpush
