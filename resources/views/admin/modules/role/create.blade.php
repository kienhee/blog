@extends('admin.layouts.master')
@section('title', 'Thêm vai trò')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
@endpush

@section('content')
    <section>
        <form id="form_role" action="{{ route('admin.roles.store') }}" method="POST">
            @csrf
            @include('admin.components.headingPage', [
                'description' => 'Thêm vai trò mới cho hệ thống',
                'button' => 'create',
                'listLink' => 'admin.roles.list',
                'buttonPermission' => 'role.create',
            ])

            <div class="row">
                <div class="col-12 col-lg-5">
                    {{-- THÔNG TIN VAI TRÒ --}}
                    <div class="card mb-4">
                        <div class="card-header border-bottom mb-3">
                            <div class="d-flex flex-column">
                                <h5 class="card-title mb-1">Thông tin vai trò</h5>
                                <small class="text-muted">Thông tin cơ bản của vai trò</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="name">Tên vai trò <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control"
                                    value="{{ old('name') }}" placeholder="Nhập tên vai trò" required maxlength="255">
                                @error('name')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    {{-- PHÂN QUYỀN --}}
                    <div class="card mb-4">
                        <div class="card-header border-bottom mb-3">
                            <div class="d-flex flex-column">
                                <h5 class="card-title mb-1">Phân quyền</h5>
                                <small class="text-muted">Chọn các quyền cho vai trò này</small>
                            </div>
                        </div>
                        <div class="card-body">
                            @if ($permissions->isEmpty())
                                <div class="alert alert-info mb-0">
                                    <i class="bx bx-info-circle me-2"></i>
                                    Chưa có quyền nào trong hệ thống. Vui lòng chạy seeder để tạo quyền.
                                </div>
                            @else
                                @foreach ($permissions as $module => $modulePermissions)
                                    <div class="mb-4">
                                        <h6 class="mb-3 text-primary">
                                            <i class="bx bx-folder me-2"></i>{{ ucfirst($module) }}
                                        </h6>
                                        <div class="row g-2">
                                            @foreach ($modulePermissions as $permission)
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                                            value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            @checked(in_array($permission->id, old('permissions', [])))>
                                                        <label class="form-check-label"
                                                            for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('.', ' ', $permission->title)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @if (!$loop->last)
                                        <hr>
                                    @endif
                                @endforeach
                                @error('permissions')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                                @error('permissions.*')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            @endif
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
    @vite(['resources/js/admin/pages/role/form.js'])
@endpush
