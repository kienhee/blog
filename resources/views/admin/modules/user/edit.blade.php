@extends('admin.layouts.master')
@section('title', 'Chỉnh sửa người dùng: ' . ($user->full_name ?: $user->email))

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endpush

@section('content')
    <section>
        <form id="form_user" action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.components.headingPage', [
                'description' => 'Chỉnh sửa thông tin người dùng hệ thống',
                'button' => 'edit',
                'listLink' => 'admin.users.list',
            ])

            <div class="row">
                <div class="col-12 col-lg-6">
                    {{-- KHỐI 1: THÔNG TIN ĐĂNG NHẬP --}}
                    <div class="card mb-4">
                        <div class="card-header border-bottom mb-3">
                            <div class="d-flex flex-column">
                                <h5 class="card-title mb-1">1. Thông tin đăng nhập</h5>
                                <small class="text-muted">Cập nhật thông tin đăng nhập nội bộ</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="email">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" id="email" {{ $user->email_verified_at ? '' : 'name=email' }}
                                    class="form-control" value="{{ old('email', $user->email) }}" placeholder="Nhập email"
                                    {{ $user->email_verified_at ? 'disabled' : 'required' }} maxlength="254">
                                @if ($user->email_verified_at)
                                    {{-- Hidden input để submit email khi disabled --}}
                                    <input type="hidden" name="email" value="{{ $user->email }}">
                                    <small class="text-success d-block mt-1">
                                        <i class="bx bx-info-circle"></i> Email đã được xác thực không thể thay đổi
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- KHỐI 2: THÔNG TIN CÁ NHÂN --}}
                    <div class="card mb-4">
                        <div class="card-header border-bottom mb-3">
                            <div class="d-flex flex-column">
                                <h5 class="card-title mb-1">2. Thông tin cá nhân</h5>
                                <small class="text-muted">Thông tin hiển thị trong hồ sơ và bài viết</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="full_name">Họ và tên</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control"
                                        value="{{ old('full_name', $user->full_name) }}" placeholder="Nhập họ và tên"
                                        maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Số điện thoại</label>
                                    <input type="text" id="phone" name="phone" class="form-control"
                                        value="{{ old('phone', $user->phone) }}" placeholder="Nhập số điện thoại"
                                        maxlength="20" inputmode="numeric" pattern="[0-9]*">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="gender">Giới tính</label>
                                    @php
                                        $currentGender = old('gender');
                                        if ($currentGender === null) {
                                            $currentGender = match ($user->gender) {
                                                0 => 'male',
                                                1 => 'female',
                                                2 => 'other',
                                                default => '',
                                            };
                                        }
                                    @endphp
                                    <select id="gender" name="gender" class="form-select">
                                        <option value="" {{ $currentGender === '' ? 'selected' : '' }}>Không chọn
                                        </option>
                                        <option value="male" {{ $currentGender === 'male' ? 'selected' : '' }}>Nam
                                        </option>
                                        <option value="female" {{ $currentGender === 'female' ? 'selected' : '' }}>Nữ
                                        </option>
                                        <option value="other" {{ $currentGender === 'other' ? 'selected' : '' }}>Khác
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="birthday">Ngày sinh</label>
                                    <input type="text" id="birthday" name="birthday" class="form-control date-picker"
                                        value="{{ old('birthday', $user->birthday) }}" placeholder="DD/MM/YYYY">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="description">Giới thiệu</label>
                                    <textarea id="description" name="description" rows="3" class="form-control"
                                        placeholder="Thông tin giới thiệu ngắn về người dùng" maxlength="255">{{ old('description', $user->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KHỐI 3: ẢNH ĐẠI DIỆN --}}
                <div class="col-12 col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header border-bottom mb-3">
                            <div class="d-flex flex-column">
                                <h5 class="card-title mb-1">3. Ảnh đại diện</h5>
                                <small class="text-muted">Khuyến nghị: hình vuông (tỉ lệ 1:1) để hiển thị đẹp trên toàn hệ
                                    thống</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start gap-3">
                                <div id="upload_box" class="flex-shrink-0 border rounded"
                                    style="width: 80px; height: 80px; overflow: hidden; background: #f5f5f9; display: flex; align-items: center; justify-content: center;">
                                    @if (old('avatar', $user->avatar))
                                        <img src="{{ old('avatar', $user->avatar) }}" alt="Avatar preview"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <i class="bx bx-user" style="font-size: 2rem; color: #a1acb8;"></i>
                                    @endif
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <button class="btn btn-primary btn-sm upload_btn mb-2" type="button"
                                        style="width: fit-content;">
                                        <i class="bx bx-upload me-1"></i> Upload new photo
                                    </button>
                                    <small class="text-muted">Allowed JPG, PNG, GIF, WebP or SVG. Max size of 2MB</small>
                                </div>
                            </div>
                            <input id="avatar" name="avatar" type="hidden"
                                value="{{ old('avatar', $user->avatar) }}">
                        </div>
                    </div>

                    {{-- KHỐI 4: MẠNG XÃ HỘI --}}
                    <div class="card mb-4">
                        <div class="card-header border-bottom mb-3">
                            <div class="d-flex flex-column">
                                <h5 class="card-title mb-1">4. Liên kết mạng xã hội</h5>
                                <small class="text-muted">Giúp hiển thị profile cá nhân đầy đủ hơn</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="twitter_url">Twitter</label>
                                <input type="url" id="twitter_url" name="twitter_url"
                                    class="form-control social-url" value="{{ old('twitter_url', $user->twitter_url) }}"
                                    placeholder="https://twitter.com/..." maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="facebook_url">Facebook</label>
                                <input type="url" id="facebook_url" name="facebook_url"
                                    class="form-control social-url"
                                    value="{{ old('facebook_url', $user->facebook_url) }}"
                                    placeholder="https://facebook.com/..." maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="instagram_url">Instagram</label>
                                <input type="url" id="instagram_url" name="instagram_url"
                                    class="form-control social-url"
                                    value="{{ old('instagram_url', $user->instagram_url) }}"
                                    placeholder="https://instagram.com/..." maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="linkedin_url">LinkedIn</label>
                                <input type="url" id="linkedin_url" name="linkedin_url"
                                    class="form-control social-url"
                                    value="{{ old('linkedin_url', $user->linkedin_url) }}"
                                    placeholder="https://linkedin.com/in/..." maxlength="255">
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
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    @vite([
        'resources/js/admin/common/uploads/upload-image-alone.js',
        'resources/js/admin/common/uploads/upload-avatar.js',
        'resources/js/admin/pages/user/form.js'
    ])
@endpush
