@extends('admin.layouts.master')
@section('title', 'Danh sách người dùng')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset_admin_url('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/lightbox2/css/lightbox.min.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
@endpush
@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Quản lý người dùng hệ thống',
            'button' => 'add',
            'buttonLink' => 'admin.users.create',
            'buttonPermission' => 'user.create',
        ])

        <div class="card">
            <div class="card-header border-bottom ">
                <div class="d-flex align-items-end row gap-6 gap-md-0 g-md-6">
                    <div class="col-md-3 mb-2">
                        <label for="created_at" class="form-label mb-1">Ngày tạo</label>
                        <input type="text" id="created_at" class="form-control date-picker" placeholder="DD/MM/YYYY" />
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="email_verified_filter" class="form-label mb-1">Trạng thái email</label>
                        <select id="email_verified_filter" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="verified">Đã xác minh</option>
                            <option value="unverified">Chưa xác minh</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-primary" id="clearFilter">Đặt lại</button>
                    </div>
                </div>
            </div>

            <div class="nav-align-top">
                <ul class="nav nav-tabs nav-fill" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 border-0 active" role="tab"
                            data-bs-toggle="tab" data-bs-target="#user_list_tab" aria-controls="user_list_tab"
                            aria-selected="true">
                            <i class="bx bx-table me-1"></i> Danh sách
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 border-0" role="tab" data-bs-toggle="tab"
                            data-bs-target="#user_trash_tab" aria-controls="user_trash_tab" aria-selected="false">
                            <i class="bx bx-user-x me-1"></i> Ngừng hoạt động
                        </button>
                    </li>
                </ul>
                <div class="tab-content border-0">
                    <div class="tab-pane fade show active" id="user_list_tab" role="tabpanel">
                        @can('user.delete')
                            <div class="card-header border-bottom mb-3" id="bulkActionsContainerUsers" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted" id="selectedCountUsers">Đã chọn: <strong>0</strong>
                                            người dùng</span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-warning btn-sm" id="bulkDeleteBtnUsers">
                                            <i class="bx bx-user-x me-1"></i> Ngừng hoạt động đã chọn
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endcan
                        <div class="card-datatable table-responsive">
                            <table class="table border-top" id="user_datatable"
                                data-url="{{ route('admin.users.ajaxGetData') }}">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllUsers" />
                                        </th>
                                        <th>STT</th>
                                        <th>Họ và tên</th>
                                        <th>Email</th>
                                        <th>Số điện thoại</th>
                                        <th>Ngày tạo</th>
                                        <th>Xác minh email</th>
                                        <th>Vai trò</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="user_trash_tab" role="tabpanel">
                        @canany(['user.update', 'user.delete'])
                            <div class="card-header border-bottom mb-3" id="bulkActionsContainerUsersTrash"
                                style="display: none;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted" id="selectedCountUsersTrash">Đã chọn: <strong>0</strong>
                                            người dùng</span>
                                    </div>
                                    <div>
                                        @can('user.update')
                                            <button type="button" class="btn btn-success btn-sm me-2" id="bulkRestoreBtnUsers">
                                                <i class="bx bx-check-circle me-1"></i> Kích hoạt lại đã chọn
                                            </button>
                                        @endcan
                                        @can('user.delete')
                                            <button type="button" class="btn btn-danger btn-sm" id="bulkForceDeleteBtnUsers">
                                                <i class="bx bx-trash me-1"></i> Xóa vĩnh viễn đã chọn
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcanany
                        <div class="card-datatable table-responsive">
                            <table class="table border-top" id="user_datatable_trash"
                                data-url="{{ route('admin.users.ajaxGetTrashedData') }}">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllUsersTrash" />
                                        </th>
                                        <th>STT</th>
                                        <th>Người dùng</th>
                                        <th>Email</th>
                                        <th>Ngày ngừng hoạt động</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Modal xác nhận ngừng hoạt động --}}
        <div class="modal fade" id="confirmDeleteUserModal" tabindex="-1" aria-labelledby="confirmDeleteUserLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteUserLabel">Xác nhận ngừng hoạt động</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn có chắc chắn muốn cho người dùng
                            "<strong id="deleteUserName" class="text-limit-1"
                                style="word-break: break-word; white-space: normal; display: inline;"></strong>"
                            <strong>ngừng hoạt động</strong>?
                            <br><small class="d-block mt-2">Tài khoản sẽ không thể đăng nhập cho đến khi được kích hoạt
                                lại.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-warning" id="confirmDeleteUserBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Ngừng hoạt động
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal xác nhận kích hoạt lại --}}
        <div class="modal fade" id="confirmRestoreUserModal" tabindex="-1" aria-labelledby="confirmRestoreUserLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmRestoreUserLabel">Xác nhận kích hoạt lại</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Bạn có chắc chắn muốn kích hoạt lại tài khoản "<strong id="restoreUserName" class="text-limit-1"
                            style="word-break: break-word; white-space: normal; display: inline;"></strong>"?
                        <br><small class="d-block mt-2 text-muted">Tài khoản sẽ có thể đăng nhập trở lại sau khi kích
                            hoạt.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-success" id="confirmRestoreUserBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Kích hoạt lại
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal xác nhận xóa vĩnh viễn --}}
        <div class="modal fade" id="confirmForceDeleteUserModal" tabindex="-1"
            aria-labelledby="confirmForceDeleteUserLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmForceDeleteUserLabel">Xác nhận xóa vĩnh viễn người dùng</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Cảnh báo:</strong> Bạn sắp xóa vĩnh viễn người dùng
                            "<strong id="forceDeleteUserName" class="text-limit-1"
                                style="word-break: break-word; white-space: normal; display: inline;"></strong>".<br>
                            Hành động này không thể hoàn tác.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmForceDeleteUserBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa vĩnh viễn
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal xác nhận ngừng hoạt động hàng loạt --}}
        <div class="modal fade" id="bulkDeleteUsersModal" tabindex="-1" aria-labelledby="bulkDeleteUsersLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkDeleteUsersLabel">Xác nhận ngừng hoạt động</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn có chắc chắn muốn cho <strong id="bulkDeleteUsersCount">0</strong> người dùng đã
                            chọn <strong>ngừng hoạt động</strong>?<br>
                            <small class="d-block mt-2">Các tài khoản sẽ không thể đăng nhập cho đến khi được kích hoạt
                                lại.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-warning" id="confirmBulkDeleteUsersBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Ngừng hoạt động
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal xác nhận kích hoạt lại hàng loạt --}}
        <div class="modal fade" id="bulkRestoreUsersModal" tabindex="-1" aria-labelledby="bulkRestoreUsersLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkRestoreUsersLabel">Xác nhận kích hoạt lại</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn có chắc chắn muốn kích hoạt lại <strong id="bulkRestoreUsersCount">0</strong> tài khoản đã
                            chọn?
                            <br><small class="d-block mt-2 text-muted">Các tài khoản sẽ có thể đăng nhập trở lại sau khi
                                kích hoạt.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-success" id="confirmBulkRestoreUsersBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Kích hoạt lại
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal xác nhận xóa vĩnh viễn hàng loạt --}}
        <div class="modal fade" id="bulkForceDeleteUsersModal" tabindex="-1"
            aria-labelledby="bulkForceDeleteUsersLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkForceDeleteUsersLabel">Xác nhận xóa vĩnh viễn</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-3">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Cảnh báo:</strong> Bạn có chắc chắn muốn xóa vĩnh viễn
                            <strong id="bulkForceDeleteUsersCount">0</strong> người dùng đã chọn?<br>
                            <small class="d-block mt-2">Hành động này không thể hoàn tác.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmBulkForceDeleteUsersBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa vĩnh viễn
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
    <script src="{{ asset_admin_url('assets/vendor/libs/lightbox2/js/lightbox.min.js') }}"></script>
    @vite(['resources/js/admin/pages/user/list.js'])
    <script>
        window.userBulkDeleteUrl = "{{ route('admin.users.bulkDelete') }}";
        window.userBulkRestoreUrl = "{{ route('admin.users.bulkRestore') }}";
        window.userBulkForceDeleteUrl = "{{ route('admin.users.bulkForceDelete') }}";
        window.userDeleteUrlTemplate = "{{ route('admin.users.destroy', ':id') }}";
        window.userRestoreUrlTemplate = "{{ route('admin.users.restore', ':id') }}";
        window.userForceDeleteUrlTemplate = "{{ route('admin.users.forceDelete', ':id') }}";
    </script>
@endpush
