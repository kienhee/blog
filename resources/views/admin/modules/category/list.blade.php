@extends('admin.layouts.master')
@section('title', 'Danh sách danh mục')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset_admin_url('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/lightbox2/css/lightbox.min.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/jstree/jstree.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
@endpush
@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Quản lý danh mục bài viết',
            'button' => 'add',
            'buttonLink' => 'admin.categories.create',
            'buttonPermission' => 'category.create',
        ])

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
            {{-- tabs --}}
            <div class="nav-align-top">
                <ul class="nav nav-tabs nav-fill" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 border-0 active" role="tab"
                            data-bs-toggle="tab" data-bs-target="#ovrview_tab" aria-controls="ovrview_tab"
                            aria-selected="true">
                            <i class="bx bx-table me-1"></i> Danh sách
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 border-0" role="tab" data-bs-toggle="tab"
                            data-bs-target="#tree_view_tab" aria-controls="tree_view_tab" aria-selected="false">
                            <i class="bx bx-git-branch me-1"></i> Tổng quan
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 border-0" role="tab" data-bs-toggle="tab"
                            data-bs-target="#trash_tab" aria-controls="trash_tab" aria-selected="false">
                            <i class="bx bx-trash me-1"></i> Thùng rác
                        </button>
                    </li>
                </ul>
                <div class="tab-content border-0">
                    <div class="tab-pane fade show active" id="ovrview_tab" role="tabpanel">
                        @can('category.delete')
                            <div class="card-header border-bottom mb-3" id="bulkActionsContainerCategories"
                                style="display: none;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted" id="selectedCountCategories">Đã chọn: <strong>0</strong>
                                            mục</span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtnCategories">
                                            <i class="bx bx-trash me-1"></i> Xóa đã chọn
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endcan
                        <div class="card-datatable table-responsive">
                            <table class="table border-top" id="category_datatable"
                                data-url="{{ route('admin.categories.ajaxGetData') }}">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllCategories" />
                                        </th>
                                        <th>STT</th>
                                        <th>Tên</th>
                                        <th>Mô tả</th>
                                        <th>Số bài viết</th>
                                        <th>Ngày tạo</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tree_view_tab" role="tabpanel">
                        <div class="row">
                            <div class="col-12 mb-3 py-3 border-dashed border-1">
                                <strong class="mb-1 d-block">Danh mục bài viết</strong>
                                <div id="jstree-ajax-post"
                                    data-url="{{ route('admin.categories.ajax-get-tree-view', ['type' => 'post']) }}">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 px-0">
                                <div class="alert alert-info d-flex align-items-center mb-0 small" role="alert">
                                    <i class="bx bx-info-circle me-2 fs-5"></i>
                                    <div>
                                        <strong>Hướng dẫn sử dụng:</strong>
                                        <ul class="mb-0 mt-1 ">
                                            <li><strong>Kéo thả:</strong> Nhấn giữ và kéo danh mục để thay đổi vị trí hoặc
                                                đặt làm danh mục con của danh mục khác. Hệ thống sẽ tự động cập nhật cấu
                                                trúc danh mục.</li>
                                            <li><strong>Double click:</strong> Nhấp đúp vào danh mục để chuyển đến trang
                                                chỉnh sửa.</li>
                                            <li><strong>Lưu ý:</strong> Không thể kéo danh mục vào chính nó hoặc vào các
                                                danh mục con của nó.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="trash_tab" role="tabpanel">
                        @canany(['category.update', 'category.delete'])
                            <div class="card-header border-bottom mb-3" id="bulkActionsContainer" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted" id="selectedCount">Đã chọn: <strong>0</strong> mục</span>
                                    </div>
                                    <div>
                                        @can('category.update')
                                            <button type="button" class="btn btn-success btn-sm me-2" id="bulkRestoreBtn">
                                                <i class="bx bx-undo me-1"></i> Khôi phục đã chọn
                                            </button>
                                        @endcan
                                        @can('category.delete')
                                            <button type="button" class="btn btn-danger btn-sm" id="bulkForceDeleteBtn">
                                                <i class="bx bx-trash me-1"></i> Xóa vĩnh viễn đã chọn
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcanany
                        <div class="card-datatable table-responsive">
                            <table class="table border-top" id="category_datatable_trash"
                                data-url="{{ route('admin.categories.ajaxGetTrashedData') }}">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllTrash" />
                                        </th>
                                        <th>STT</th>
                                        <th>Tên</th>
                                        <th>Số bài viết</th>
                                        <th>Ngày xóa</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteLabel">Xác nhận xóa danh mục</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Cảnh báo chung -->
                        <div class="alert alert-warning mb-3">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Thông báo:</strong> Bạn sắp xóa danh mục "<span id="deleteCategoryName"
                                class="fw-bold"></span>".<br>
                            Danh mục sẽ được chuyển vào <strong>Thùng rác</strong> và bạn có thể khôi phục lại sau.
                        </div>

                        <!-- Thông tin danh mục -->
                        <div class="mb-3">
                            <h6 class="mb-2 fw-bold">Thông tin danh mục:</h6>
                            <ul class="list-unstyled mb-2">
                                <li class="mb-1"><strong>Số danh mục con:</strong> <span id="deleteChildrenCount"
                                        class="badge bg-label-info">0</span></li>
                                <li class="mb-1"><strong>Số bài viết trực tiếp:</strong> <span id="deletePostCount"
                                        class="badge bg-label-success">0</span></li>
                            </ul>
                        </div>

                        <!-- Cây danh mục con -->
                        <div id="deleteCategoryTreeContainer" class="mb-3" style="display: none;">
                            <h6 class="mb-2 fw-bold">Cây danh mục con sẽ bị ảnh hưởng:</h6>
                            <div
                                style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.75rem; background-color: #f8f9fa;">
                                <ul id="deleteCategoryTree" class="mb-0" style="list-style: none; padding-left: 0;">
                                </ul>
                            </div>
                        </div>

                        <!-- Cảnh báo khi có children -->
                        <div id="deleteWarningChildren" class="alert alert-warning mb-3" style="display: none;">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Khi xóa, các danh mục con sẽ được đưa lên làm danh mục cha <strong>(Danh
                                mục gần nhất)</strong>.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Confirmation Modal -->
        <div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-labelledby="confirmRestoreLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmRestoreLabel">Xác nhận khôi phục danh mục</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Bạn có chắc chắn muốn khôi phục danh mục "<strong id="restoreTitle" class="text-limit-1"
                            style="word-break: break-word; white-space: normal; display: inline;"></strong>"?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-success" id="confirmRestoreBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Khôi phục
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Force Delete Confirmation Modal -->
        <div class="modal fade" id="confirmForceDeleteModal" tabindex="-1" aria-labelledby="confirmForceDeleteLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmForceDeleteLabel">Xác nhận xóa vĩnh viễn danh mục</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-3">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Cảnh báo:</strong> Bạn sắp xóa vĩnh viễn danh mục "<strong id="forceDeleteTitle"
                                class="text-limit-1"
                                style="word-break: break-word; white-space: normal; display: inline;"></strong>".<br>
                            Hành động này không thể hoàn tác và sẽ xóa vĩnh viễn khỏi hệ thống.
                        </div>
                        <div class="alert alert-warning mt-3 mb-0 d-none" id="forceDeletePostAlert">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Chú ý:</strong> Danh mục này hiện có bài viết. Tất cả bài viết sẽ được chuyển vào danh
                            mục
                            <strong>"Chưa phân loại"</strong> trước khi xóa vĩnh viễn.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmForceDeleteBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa vĩnh viễn
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Delete Confirmation Modal (Main List) -->
        <div class="modal fade" id="bulkDeleteModalCategories" tabindex="-1"
            aria-labelledby="bulkDeleteLabelCategories" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkDeleteLabelCategories">Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn có chắc chắn muốn xóa <strong id="bulkDeleteCountCategories">0</strong> danh mục đã
                            chọn?<br>
                            <small class="d-block mt-2">Các danh mục sẽ được chuyển vào <strong>Thùng rác</strong> và bạn
                                có thể khôi phục lại sau.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtnCategories">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Restore Confirmation Modal -->
        <div class="modal fade" id="bulkRestoreModal" tabindex="-1" aria-labelledby="bulkRestoreLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkRestoreLabel">Xác nhận khôi phục</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn có chắc chắn muốn khôi phục <strong id="bulkRestoreCount">0</strong> danh mục đã chọn?
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-success" id="confirmBulkRestoreBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Khôi phục
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Force Delete Confirmation Modal -->
        <div class="modal fade" id="bulkForceDeleteModal" tabindex="-1" aria-labelledby="bulkForceDeleteLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkForceDeleteLabel">Xác nhận xóa vĩnh viễn</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-3">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Cảnh báo:</strong> Bạn có chắc chắn muốn xóa vĩnh viễn <strong
                                id="bulkForceDeleteCount">0</strong> danh mục đã chọn?<br>
                            <small class="d-block mt-2">Hành động này không thể hoàn tác và sẽ xóa vĩnh viễn khỏi hệ
                                thống.</small>
                        </div>
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Các bài viết của các danh mục này sẽ được chuyển vào danh mục
                            <strong>"Chưa phân loại"</strong> trước khi xóa vĩnh viễn.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmBulkForceDeleteBtn">
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
    <script src="{{ asset_admin_url('assets/vendor/libs/lightbox2/js/lightbox.min.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/jstree/jstree.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    @vite(['resources/js/admin/pages/category/list.js', 'resources/js/admin/pages/category/tree.js'])
    <script>
        // Define routes for category
        window.categoryBulkDeleteUrl = "{{ route('admin.categories.bulkDelete') }}";
        window.categoryBulkRestoreUrl = "{{ route('admin.categories.bulkRestore') }}";
        window.categoryBulkForceDeleteUrl = "{{ route('admin.categories.bulkForceDelete') }}";
        window.categoryDeleteInfoUrl = "{{ route('admin.categories.deleteInfo', ':id') }}";
        window.categoryUpdateOrderUrl = "{{ route('admin.categories.updateOrder') }}";
        window.categoryEditUrl = "{{ route('admin.categories.edit', ':id') }}";
    </script>
@endpush
