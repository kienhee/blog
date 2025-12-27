@extends('admin.layouts.master')
@section('title', 'Chỉnh sửa hashtag')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
@endpush

@section('content')
    <section>
        <form id="form_hashtag" action="{{ route('admin.hashtags.update', $data->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.components.headingPage', [
                'description' => 'Cập nhật thông tin hashtag',
                'listLink' => 'admin.hashtags.list',
                'button' => 'edit',
                'buttonPermission' => 'hashtag.update',
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
                                    name="name" value="{{ old('name', $data->name) }}" maxlength="20" required />
                            </div>

                            {{-- Slug --}}
                            <div class="mb-3">
                                <label class="form-label" for="outputSlug">Slug <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="outputSlug" name="slug"
                                    value="{{ old('slug', $data->slug) }}" maxlength="20" />
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
