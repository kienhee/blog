@extends('admin.layouts.master')
@section('title', 'Thêm người dùng')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/select2/select2.css') }}" />
@endpush

@section('content')
    <section>
        <form id="form_user" action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            @include('admin.components.headingPage', [
                'description' => 'Thêm người dùng mới cho hệ thống',
                'button' => 'create',
                'listLink' => 'admin.users.list',
                'buttonPermission' => 'user.create',
            ])

            <div class="row">
                <div class="col-12 col-lg-6">
                    {{-- KHỐI 1: THÔNG TIN ĐĂNG NHẬP --}}
                    <div class="card mb-4">
                        <div class="card-header border-bottom mb-3">
                            <div class="d-flex flex-column">
                                <h5 class="card-title mb-1">1. Thông tin đăng nhập</h5>
                                <small class="text-muted">Dùng để đăng nhập vào hệ thống nội bộ</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="{{ old('email') }}" placeholder="Nhập email" required maxlength="254">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="Nhập mật khẩu" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="form-control" placeholder="Nhập lại mật khẩu" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="roles">Vai trò <span class="text-danger">*</span></label>
                                <select id="roles" name="roles[]"
                                    class="form-select select2 @error('roles') is-invalid @enderror" multiple required>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected(in_array($role->id, old('roles', [])))>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Chọn một hoặc nhiều vai trò cho người dùng này</small>
                                @error('roles')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('roles.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
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
                                    <label class="form-label" for="full_name">Họ và tên <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="full_name" name="full_name" class="form-control"
                                        value="{{ old('full_name') }}" placeholder="Nhập họ và tên" maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Số điện thoại</label>
                                    <input type="text" id="phone" name="phone" class="form-control"
                                        value="{{ old('phone') }}" placeholder="Nhập số điện thoại" minlength="10" maxlength="10"
                                        inputmode="numeric" pattern="[0-9]*">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="gender">Giới tính</label>
                                    <select id="gender" name="gender" class="form-select">
                                        <option value="">Không chọn</option>
                                        <option value="male" @selected(old('gender') === 'male')>Nam</option>
                                        <option value="female" @selected(old('gender') === 'female')>Nữ</option>
                                        <option value="other" @selected(old('gender') === 'other')>Khác</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="birthday">Ngày sinh</label>
                                    <input type="text" id="birthday" name="birthday"
                                        class="form-control date-picker" value="{{ old('birthday') }}"
                                        placeholder="DD/MM/YYYY">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="description">Giới thiệu</label>
                                    <textarea id="description" name="description" rows="3" class="form-control"
                                        placeholder="Thông tin giới thiệu ngắn về người dùng" maxlength="255">{{ old('description') }}</textarea>
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
                                    <i class="bx bx-user" style="font-size: 2rem; color: #a1acb8;"></i>
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <button class="btn btn-primary btn-sm upload_btn mb-2" type="button"
                                        style="width: fit-content;">
                                        <i class="bx bx-upload me-1"></i> Upload new photo
                                    </button>
                                    <small class="text-muted">Allowed JPG, PNG, GIF, WebP or SVG. Max size of 2MB</small>
                                </div>
                            </div>
                            <input id="avatar" name="avatar" type="hidden" value="{{ old('avatar') }}">
                        </div>
                    </div>

                    {{-- KHỐI 4: MẠNG XÃ HỘI --}}
                    <div class="card mb-4">
                        <div class="card-header border-bottom">
                            <div class="d-flex flex-column">
                                <h5 class="card-title mb-1">4. Liên kết mạng xã hội</h5>
                                <small class="text-muted">Giúp hiển thị profile cá nhân đầy đủ hơn</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="twitter_url">Twitter</label>
                                <input type="url" id="twitter_url" name="twitter_url"
                                    class="form-control social-url" value="{{ old('twitter_url') }}"
                                    placeholder="https://twitter.com/..." maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="facebook_url">Facebook</label>
                                <input type="url" id="facebook_url" name="facebook_url"
                                    class="form-control social-url" value="{{ old('facebook_url') }}"
                                    placeholder="https://facebook.com/..." maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="instagram_url">Instagram</label>
                                <input type="url" id="instagram_url" name="instagram_url"
                                    class="form-control social-url" value="{{ old('instagram_url') }}"
                                    placeholder="https://instagram.com/..." maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="linkedin_url">LinkedIn</label>
                                <input type="url" id="linkedin_url" name="linkedin_url"
                                    class="form-control social-url" value="{{ old('linkedin_url') }}"
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
    <script src="{{ asset_admin_url('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    @vite(['resources/js/admin/common/forms/forms-selects.js', 'resources/js/admin/common/uploads/upload-image-alone.js', 'resources/js/admin/common/uploads/upload-avatar.js', 'resources/js/admin/pages/user/form.js'])
@endpush
