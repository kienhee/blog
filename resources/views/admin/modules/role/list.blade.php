@extends('admin.layouts.master')
@section('title', 'Danh sách vai trò')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset_admin_url('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
@endpush
@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Quản lý vai trò hệ thống',
            'button' => 'add',
            'buttonLink' => 'admin.roles.create',
            'buttonPermission' => 'role.create',
        ])

        <div class="card">
            <div class="card-header border-bottom ">
                <div class="d-flex align-items-end row gap-6 gap-md-0 g-md-6">
                    <div class="col-md-3 mb-2">
                        <label for="created_at" class="form-label mb-1">Ngày tạo</label>
                        <input type="text" id="created_at" class="form-control date-picker" placeholder="DD/MM/YYYY" />
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-primary" id="clearFilter">Đặt lại</button>
                    </div>
                </div>
            </div>

            <div class="card-datatable table-responsive">
                <table class="table border-top" id="role_datatable" data-url="{{ route('admin.roles.ajaxGetData') }}">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" class="form-check-input" id="selectAllRoles" />
                            </th>
                            <th>STT</th>
                            <th>Tên vai trò</th>
                            <th>Số quyền</th>
                            <th>Số người dùng</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        {{-- Modal xác nhận xóa --}}
        <div class="modal fade" id="confirmDeleteRoleModal" tabindex="-1" aria-labelledby="confirmDeleteRoleLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteRoleLabel">Xác nhận xóa vai trò</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            <i class="bx bx-error-circle me-2"></i>
                            Bạn có chắc chắn muốn xóa vai trò
                            "<strong id="deleteRoleName" class="text-limit-1"
                                style="word-break: break-word; white-space: normal; display: inline;"></strong>"?
                            <br><small class="d-block mt-2">Hành động này không thể hoàn tác.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteRoleBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    @vite(['resources/js/admin/pages/role/list.js'])
    <script>
        window.roleDeleteUrlTemplate = "{{ route('admin.roles.destroy', ':id') }}";
    </script>
@endpush
