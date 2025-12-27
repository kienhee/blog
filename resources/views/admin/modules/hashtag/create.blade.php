@extends('admin.layouts.master')
@section('title', 'Thêm mới hashtag')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
@endpush

@section('content')
    <section>
        <form id="form_hashtag" action="{{ route('admin.hashtags.store') }}" method="POST">
            @csrf
            @include('admin.components.headingPage', [
                'description' => 'Thêm hashtag mới cho bài viết',
                'listLink' => 'admin.hashtags.list',
                'button' => 'create',
                'buttonPermission' => 'hashtag.create',
            ])
            <div class="row">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Thông tin hashtag</h5>
                        </div>
                        <div class="card-body">
                            {{-- Tên hashtag --}}
                            <div class="mb-3">
                                <label class="form-label" for="inputSlug">Tên hashtag <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="inputSlug" placeholder="Nhập tên hashtag..."
                                    name="name" value="{{ old('name') }}" maxlength="20" required />
                            </div>

                            {{-- Slug --}}
                            <div class="mb-3">
                                <label class="form-label" for="outputSlug">Slug <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="outputSlug" name="slug"
                                    value="{{ old('slug') }}" maxlength="20" />
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
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    @vite(['resources/js/admin/pages/hashtag/slug.js', 'resources/js/admin/pages/hashtag/form.js'])
@endpush
