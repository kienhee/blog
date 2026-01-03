@extends('admin.layouts.master')
@section('title', 'Quản lý tài khoản')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <style>
        .sortable-ghost {
            opacity: 0.4;
        }
        tbody tr {
            cursor: move;
        }
    </style>
@endpush

@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Quản lý thông tin tài khoản',
            'button' => 'add',
            'buttonLink' => '#',
            'buttonPermission' => 'account.create',
            'buttonId' => 'btnAddAccount',
        ])
        @include('admin.components.showMessage')
        
        <div class="card">
            <div class="card-header border-bottom">
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
            
            @can('account.delete')
            <div class="card-header border-bottom mb-3" id="bulkActionsContainer" style="display: none;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted" id="selectedCount">Đã chọn: <strong>0</strong> mục</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn">
                            <i class="bx bx-trash me-1"></i> Xóa đã chọn
                        </button>
                    </div>
                </div>
            </div>
            @endcan
            
            <div class="card-datatable table-responsive">
                <table class="table border-top" id="account_datatable" data-url="{{ route('admin.accounts.ajaxGetData') }}">
                    <thead>
                        <tr>
                            @can('account.delete')
                            <th width="50">
                                <input type="checkbox" class="form-check-input" id="selectAllAccounts" />
                            </th>
                            @endcan
                            <th width="50">STT</th>
                            <th>Tên tài khoản</th>
                            <th>Mật khẩu</th>
                            <th>Loại</th>
                            <th>Ghi chú</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Offcanvas Form -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="accountOffcanvas" aria-labelledby="accountOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="accountOffcanvasLabel" class="offcanvas-title">Thêm tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form id="accountForm">
                    <input type="hidden" id="accountId" name="id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label" for="accountName">Tên tài khoản <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="accountName" name="name" 
                            placeholder="Nhập tên tài khoản" required maxlength="255" />
                        <div class="invalid-feedback"></div>
                    </div>
                    
                   
                    
                    <div class="mb-3 form-password-toggle">
                        <label class="form-label" for="accountPassword">
                            Mật khẩu <span class="text-danger" id="passwordRequired">*</span>
                            <small class="text-muted" id="passwordOptional" style="display: none;">(Để trống nếu không đổi)</small>
                        </label>
                        <div class="input-group input-group-merge">
                            <input type="password" class="form-control" id="accountPassword" name="password" 
                                placeholder="Nhập mật khẩu" />
                            <span class="input-group-text cursor-pointer" id="togglePassword">
                                <i class="bx bx-hide"></i>
                            </span>
                            <button type="button" class="btn btn-outline-secondary" id="generatePasswordBtn" title="Tạo mật khẩu mạnh">
                                <i class="bx bx-key"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="accountType">Loại tài khoản</label>
                        <input type="text" class="form-control" id="accountType" name="type" 
                            placeholder="Nhập loại tài khoản" maxlength="255" />
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="accountNote">Ghi chú</label>
                        <textarea class="form-control" id="accountNote" name="note" rows="3" 
                            placeholder="Nhập ghi chú (nếu có)"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Hủy</button>
                        <button type="submit" class="btn btn-primary" id="saveAccountBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteLabel">Xác nhận xóa tài khoản</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Thông báo:</strong> Bạn sắp xóa tài khoản "<span id="deleteTitle" class="fw-bold"></span>".
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Delete Confirmation Modal -->
        <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkDeleteLabel">Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn có chắc chắn muốn xóa <strong id="bulkDeleteCount">0</strong> tài khoản đã chọn?
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Password Modal -->
        <div class="modal fade" id="viewPasswordModal" tabindex="-1" aria-labelledby="viewPasswordLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewPasswordLabel">Xem mật khẩu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="viewPasswordForm">
                            <div class="mb-3">
                                <label class="form-label" for="userPassword">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="userPassword" name="user_password" 
                                    placeholder="Nhập mật khẩu" required autofocus />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3" id="passwordResult" style="display: none;">
                                <label class="form-label">Mật khẩu tài khoản</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="displayPassword" readonly />
                                    <button type="button" class="btn btn-outline-secondary" id="copyPasswordBtn" title="Sao chép">
                                        <i class="bx bx-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" id="verifyPasswordBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Xác thực
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
    <script src="{{ asset_admin_url('assets/vendor/libs/sortablejs/sortable.js') }}"></script>
    <script>
        // Define routes
        window.accountStoreUrl = "{{ route('admin.accounts.store') }}";
        window.accountUpdateUrl = "{{ route('admin.accounts.update', ':id') }}";
        window.accountEditUrl = "{{ route('admin.accounts.edit', ':id') }}";
        window.accountViewPasswordUrl = "{{ route('admin.accounts.viewPassword', ':id') }}";
        window.accountBulkDeleteUrl = "{{ route('admin.accounts.bulkDelete') }}";
        window.accountUpdateOrderUrl = "{{ route('admin.accounts.updateOrder') }}";
        window.accountGeneratePasswordUrl = "{{ route('admin.accounts.generatePassword') }}";
    </script>
    @vite(['resources/js/admin/pages/account/list.js'])
@endpush
