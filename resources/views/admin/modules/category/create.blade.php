@extends('admin.layouts.master')
@section('title', 'Thêm mới danh mục')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/jstree/jstree.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css') }}" />
    <style>
        /* Style cho node bị disabled trong jstree */
        #jstree-parent .jstree-node.jstree-disabled>.jstree-anchor {
            color: #b0b0b0 !important;
            opacity: 0.5;
            cursor: not-allowed !important;
        }

        #jstree-parent .jstree-node.jstree-disabled>.jstree-anchor:hover {
            color: #999 !important;
            cursor: not-allowed !important;
            background-color: transparent !important;
        }

        #jstree-parent .jstree-node.jstree-disabled>.jstree-anchor .jstree-icon {
            opacity: 0.4;
        }
    </style>
@endpush

@section('content')
    <section>
        <form id="form_category" action="{{ route('admin.categories.store') }}" method="POST">
            @csrf
            @include('admin.components.headingPage', [
                'description' => 'Thêm danh mục cho bài viết',
                'listLink' => 'admin.categories.list',
                'button' => 'create',
                'buttonPermission' => 'category.create',
            ])
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Thông tin danh mục</h5>
                        </div>
                        <div class="card-body">
                            {{-- Tên danh mục --}}
                            <div class="mb-3">
                                <label class="form-label" for="inputSlug">Tên danh mục <span class="text-danger">*</span>
                                    <span class="text-muted">(Nên dưới 60 ký tự để tối ưu SEO)</span></label>
                                <input type="text" class="form-control" id="inputSlug" placeholder="Nhập tên danh mục..."
                                    name="name" value="{{ old('name') }}" required />
                            </div>

                            {{-- Slug --}}
                            <div class="mb-3">
                                <label class="form-label" for="outputSlug">Slug <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="outputSlug" name="slug"
                                    value="{{ old('slug') }}" />
                            </div>

                            {{-- Mô tả --}}
                            <div class="mb-3">
                                <label class="form-label" for="description">Mô tả <span class="text-muted">(Optional - Nên
                                        dưới 150 ký tự để tối ưu SEO)</span></label>
                                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Nhập mô tả danh mục...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cột bên phải --}}
                <div class="col-12 col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0" id="upload-avatar">Ảnh bìa</h5>
                            <small class="text-muted">(Tùy chọn)</small>
                        </div>
                        <div class="card-body">
                            <div id="upload_box">
                                <div class="my-5 d-flex justify-content-center align-items-center">
                                    <button class="btn bg-label-primary upload_btn">Chọn ảnh từ máy</button>
                                </div>
                            </div>
                            <div class="fallback">
                                <input id="thumbnail" name="thumbnail" type="hidden" value="{{ old('thumbnail') }}" />
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Cấu hình danh mục</h5>
                        </div>
                        <div class="card-body">
                            {{-- Danh mục cha --}}
                            <div class="mb-3">
                                <label class="form-label" for="parent_id">Danh mục cha</label>
                                <div id="jstree-parent"
                                    data-url="{{ route('admin.categories.ajax-get-category-by-type') }}"></div>
                                <input type="hidden" name="parent_id" id="parent_id">
                                <div class="mt-3 alert alert-info d-flex align-items-start py-2 px-3 mb-0" role="alert">
                                    <i class="bx bx-info-circle me-2 mt-1"></i>
                                    <div class="small">
                                        <strong class="d-block mb-1">Hướng dẫn:</strong>
                                        <ul class="mb-0 small p-0">
                                            <li>Mặc định nếu không chọn danh mục thì nó sẽ là <strong>danh mục cha</strong>
                                            </li>
                                            <li>Các danh mục bị làm mờ sẽ không thể chọn được</li>
                                            <li>Không thể chọn danh mục <strong>hiện tại</strong> và <strong>con của danh
                                                    mục hiện tại</strong></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/jstree/jstree.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js') }}"></script>
    @vite([
        'resources/js/admin/common/forms/generate-slug.js',
        'resources/js/admin/common/uploads/upload-image-alone.js',
        'resources/js/admin/pages/category/form.js'
    ])
@endpush
