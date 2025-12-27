@extends('admin.layouts.master')
@section('title', 'Danh sách bài viết')
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset_admin_url('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/lightbox2/css/lightbox.min.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/animate-css/animate.css') }}" />
@endpush
@section('content')
    <section>
        @include('admin.components.headingPage', [
            'description' => 'Quản lý các bài viết trên blog hoặc trang tin tức',
            'button' => 'add',
            'buttonLink' => 'admin.posts.create',
            'buttonPermission' => 'post.create',
        ])
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title">Bộ lọc</h5>
                <div class="d-flex align-items-end row pt-4 gap-6 gap-md-0 g-md-6">
                    <div class="col-md-3 mb-2">
                        <label for="status" class="form-label mb-1">Trạng thái</label>
                        <select id="status" class="form-select text-capitalize">
                            <option value="">Tất cả</option>
                            @foreach ($statusLabels as $status => $label)
                                <option value="{{ $status }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="category_id" class="form-label mb-1">Danh mục</label>
                        <select id="category_id" class="form-select text-capitalize">
                            <option value="">Chọn danh mục</option>
                            {!! App\Models\Category::renderOptions($categories) !!}
                        </select>
                    </div>
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
                            data-bs-toggle="tab" data-bs-target="#posts_tab" aria-controls="posts_tab" aria-selected="true">
                            <i class="bx bx-list-ul me-1"></i> Danh sách
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
                    <div class="tab-pane fade show active" id="posts_tab" role="tabpanel">
                        @canany(['post.update', 'post.delete'])
                            <div class="card-header border-bottom mb-3" id="bulkActionsContainerPosts" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted" id="selectedCountPosts">Đã chọn: <strong>0</strong> mục</span>
                                    </div>
                                    <div>
                                        @can('post.update')
                                            <button type="button" class="btn btn-outline-primary btn-sm me-2" id="bulkMoveBtn">
                                                <i class="bx bx-transfer me-1"></i> Chuyển danh mục
                                            </button>
                                        @endcan
                                        @can('post.delete')
                                            <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn">
                                                <i class="bx bx-trash me-1"></i> Xóa đã chọn
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcanany
                        <div class="card-datatable table-responsive">
                            <table class="table border-top" id="datatable_blog">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllPosts" />
                                        </th>
                                        <th>STT</th>
                                        <th>Tiêu đề</th>
                                        <th>Trạng thái</th>
                                        <th>Danh mục</th>
                                        <th>Bật bình luận</th>
                                        <th>Lượt xem</th>
                                        <th>Ngày tạo</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="trash_tab" role="tabpanel">
                        @canany(['post.update', 'post.delete'])
                            <div class="card-header border-bottom mb-3" id="bulkActionsContainer" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted" id="selectedCount">Đã chọn: <strong>0</strong> mục</span>
                                    </div>
                                    <div>
                                        @can('post.update')
                                            <button type="button" class="btn btn-success btn-sm me-2" id="bulkRestoreBtn">
                                                <i class="bx bx-undo me-1"></i> Khôi phục đã chọn
                                            </button>
                                        @endcan
                                        @can('post.delete')
                                            <button type="button" class="btn btn-danger btn-sm" id="bulkForceDeleteBtn">
                                                <i class="bx bx-trash me-1"></i> Xóa vĩnh viễn đã chọn
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endcanany
                        <div class="card-datatable table-responsive">
                            <table class="table border-top" id="datatable_blog_trash">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllTrash" />
                                        </th>
                                        <th>STT</th>
                                        <th>Tiêu đề</th>
                                        <th>Trạng thái</th>
                                        <th>Danh mục</th>
                                        <th>Bật bình luận</th>
                                        <th>Lượt xem</th>
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
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteLabel">Xác nhận xóa bài viết</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Thông báo:</strong> Bạn sắp xóa bài viết "<strong id="deleteTitle"
                                class="text-limit-1"
                                style="word-break: break-word; white-space: normal; display: inline;"></strong>".<br>
                            Bài viết sẽ được chuyển vào <strong>Thùng rác</strong> và bạn có thể khôi phục lại sau.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <form id="deleteForm" method="POST" action="#">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                    aria-hidden="true"></span>
                                Xóa
                            </button>
                        </form>
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
                        <h5 class="modal-title" id="confirmRestoreLabel">Xác nhận khôi phục bài viết</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Bạn có chắc chắn muốn khôi phục bài viết "<strong id="restoreTitle" class="text-limit-1"
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
                        <h5 class="modal-title" id="confirmForceDeleteLabel">Xác nhận xóa vĩnh viễn bài viết</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-0">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Cảnh báo:</strong> Bạn sắp xóa vĩnh viễn bài viết "<strong id="forceDeleteTitle"
                                class="text-limit-1"
                                style="word-break: break-word; white-space: normal; display: inline;"></strong>".<br>
                            Hành động này không thể hoàn tác và sẽ xóa vĩnh viễn khỏi hệ thống.
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
                            Bạn có chắc chắn muốn khôi phục <strong id="bulkRestoreCount">0</strong> bài viết đã chọn?
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

        <!-- Bulk Delete Confirmation Modal -->
        <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkDeleteLabel">Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn có chắc chắn muốn xóa <strong id="bulkDeleteCount">0</strong> bài viết đã chọn?<br>
                            <small class="d-block mt-2">Các bài viết sẽ được chuyển vào <strong>Thùng rác</strong> và bạn
                                có thể khôi phục lại sau.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Xóa
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
                        <div class="alert alert-danger mb-0">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Cảnh báo:</strong> Bạn có chắc chắn muốn xóa vĩnh viễn <strong
                                id="bulkForceDeleteCount">0</strong> bài viết đã chọn?<br>
                            <small class="d-block mt-2">Hành động này không thể hoàn tác và sẽ xóa vĩnh viễn khỏi hệ
                                thống.</small>
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

        <!-- Bulk Move Category Modal -->
        <div class="modal fade" id="bulkMoveModal" tabindex="-1" aria-labelledby="bulkMoveLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkMoveLabel">Chuyển danh mục</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="bx bx-info-circle me-2"></i>
                            Bạn đang chuyển <strong id="bulkMoveCount">0</strong> bài viết đã chọn sang danh mục khác.
                        </div>
                        <div class="mb-3">
                            <label for="bulkMoveCategory" class="form-label">Chọn danh mục đích</label>
                            <select id="bulkMoveCategory" class="form-select">
                                <option value="">-- Chọn danh mục --</option>
                                {!! App\Models\Category::renderOptions($categories) !!}
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-primary" id="confirmBulkMoveBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            Chuyển danh mục
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
    <script src="{{ asset_admin_url('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script>
        // Define routes for post list
        window.postsListUrl = "{{ route('admin.posts.ajaxGetData') }}";
        window.postsTrashListUrl = "{{ route('admin.posts.ajaxGetTrashedData') }}";
        window.postBulkDeleteUrl = "{{ route('admin.posts.bulkDelete') }}";
        window.postBulkRestoreUrl = "{{ route('admin.posts.bulkRestore') }}";
        window.postBulkForceDeleteUrl = "{{ route('admin.posts.bulkForceDelete') }}";
        window.postBulkMoveUrl = "{{ route('admin.posts.bulkMoveCategory') }}";
    </script>
    @vite(['resources/js/admin/pages/post/list.js'])
@endpush
