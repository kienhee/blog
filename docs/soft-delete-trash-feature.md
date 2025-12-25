# Tài liệu: Chức năng Xóa vào Thùng rác và Khôi phục (Soft Delete & Restore)

## Tổng quan

Tài liệu này mô tả cách triển khai chức năng **Soft Delete** (Xóa mềm) và **Restore** (Khôi phục) cho các module trong hệ thống admin. Khi xóa một record, nó sẽ được chuyển vào "Thùng rác" thay vì bị xóa vĩnh viễn, và có thể được khôi phục lại sau.

## Kiến trúc tổng quan

Chức năng này được triển khai qua các thành phần sau:

1. **Model**: Sử dụng `SoftDeletes` trait
2. **Repository**: Xử lý logic truy vấn và render DataTable
3. **Controller**: Xử lý các request restore/force delete
4. **Routes**: Định nghĩa các route cho trash operations
5. **Views**: Hiển thị tab "Thùng rác" với DataTable
6. **JavaScript**: Xử lý checkbox selection và bulk actions

---

## 1. Model Layer

### 1.1. Sử dụng SoftDeletes Trait

Trong Model, import và sử dụng `SoftDeletes` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        // ... các fields khác
    ];

    protected $dates = ['deleted_at'];
}
```

**Lưu ý:**
- Migration phải có cột `deleted_at` (timestamp, nullable)
- `SoftDeletes` trait tự động thêm `deleted_at` vào `$dates`

### 1.2. Migration Example

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    // ... các cột khác
    $table->timestamp('deleted_at')->nullable();
    $table->timestamps();
});
```

---

## 2. Repository Layer

### 2.1. Các methods cần implement

Repository cần có các methods sau:

#### `gridTrashedData()`
Lấy dữ liệu đã bị soft delete để hiển thị trong tab "Thùng rác":

```php
/**
 * Get trashed data for DataTables
 */
public function gridTrashedData()
{
    $query = $this->model::onlyTrashed();
    $query->select([
        'posts.id',
        'posts.title as title',
        'posts.status as status',
        'posts.created_at as created_at',
        'posts.deleted_at as deleted_at',
        // ... các fields khác
    ])
    ->leftJoin('categories', 'posts.category_id', '=', 'categories.id');

    return $query;
}
```

**Lưu ý:**
- Sử dụng `onlyTrashed()` để chỉ lấy các record đã bị soft delete
- Có thể join với các bảng liên quan nếu cần

#### `renderTrashedDataTables($data)`
Render DataTable cho dữ liệu trong thùng rác:

```php
/**
 * Render DataTables for trashed items
 */
public function renderTrashedDataTables($data)
{
    return DataTables::of($data)
        ->addIndexColumn()
        ->addColumn('checkbox_html', function ($row) {
            return '<input type="checkbox" class="form-check-input row-checkbox" value="'.$row->id.'" />';
        })
        ->addColumn('title_html', function ($row) {
            // Render HTML cho title với thumbnail, etc.
            return '...';
        })
        ->addColumn('deleted_at_html', function ($row) {
            $deletedAt = $row->deleted_at;
            return '<span class="text-muted">'.$deletedAt->format('d/m/Y H:i').'</span>';
        })
        ->addColumn('action_html', function ($row) {
            $restoreUrl = route('admin.posts.restore', $row->id);
            $forceDeleteUrl = route('admin.posts.forceDelete', $row->id);
            
            return '
                <div class="d-inline-block text-nowrap">
                    <button type="button" class="btn btn-sm btn-icon btn-success btn-restore" 
                        data-url="'.$restoreUrl.'" data-title="'.htmlspecialchars($row->title).'">
                        <i class="bx bx-undo"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon text-danger btn-force-delete" 
                        data-url="'.$forceDeleteUrl.'" data-title="'.htmlspecialchars($row->title).'">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            ';
        })
        ->rawColumns(['checkbox_html', 'title_html', 'deleted_at_html', 'action_html'])
        ->make(true);
}
```

**Lưu ý:**
- **Bắt buộc** phải có column `checkbox_html` để hỗ trợ bulk actions
- Column `deleted_at_html` hiển thị thời gian xóa
- Column `action_html` có 2 buttons: Restore và Force Delete

#### `restore($id)`
Khôi phục một record đã bị soft delete:

```php
/**
 * Restore a trashed item
 */
public function restore($id)
{
    $item = $this->model::withTrashed()->find($id);
    if ($item && $item->trashed()) {
        return $item->restore();
    }
    return false;
}
```

#### `forceDelete($id)`
Xóa vĩnh viễn một record:

```php
/**
 * Force delete an item
 */
public function forceDelete($id)
{
    $item = $this->model::withTrashed()->find($id);
    if ($item && $item->trashed()) {
        return $item->forceDelete();
    }
    return false;
}
```

#### `bulkRestore(array $ids)`
Khôi phục nhiều records cùng lúc:

```php
/**
 * Bulk restore items
 */
public function bulkRestore(array $ids)
{
    return $this->model::withTrashed()
        ->whereIn('id', $ids)
        ->whereNotNull('deleted_at')
        ->restore();
}
```

#### `bulkForceDelete(array $ids)`
Xóa vĩnh viễn nhiều records cùng lúc:

```php
/**
 * Bulk force delete items
 */
public function bulkForceDelete(array $ids)
{
    return $this->model::withTrashed()
        ->whereIn('id', $ids)
        ->whereNotNull('deleted_at')
        ->forceDelete();
}
```

---

## 3. Controller Layer

### 3.1. Các methods cần implement

#### `ajaxGetTrashedData()`
Lấy dữ liệu trashed cho DataTable:

```php
/**
 * Get trashed data for DataTables
 */
public function ajaxGetTrashedData()
{
    $grid = $this->repository->gridTrashedData();
    $data = $this->repository->filterData($grid);
    
    return $this->repository->renderTrashedDataTables($data);
}
```

#### `restore($id)`
Khôi phục một record:

```php
/**
 * Restore a trashed item
 */
public function restore($id)
{
    try {
        $item = \App\Models\Post::withTrashed()->find($id);
        if (!$item || !$item->trashed()) {
            return response()->json([
                'status' => false,
                'message' => 'Item không tồn tại trong thùng rác',
            ], 404);
        }

        $this->repository->restore($id);

        return response()->json([
            'status' => true,
            'message' => 'Khôi phục thành công',
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Có lỗi xảy ra khi khôi phục: ' . $e->getMessage(),
        ], 500);
    }
}
```

#### `forceDelete($id)`
Xóa vĩnh viễn một record:

```php
/**
 * Force delete a trashed item
 */
public function forceDelete($id)
{
    try {
        $item = \App\Models\Post::withTrashed()->find($id);
        if (!$item || !$item->trashed()) {
            return response()->json([
                'status' => false,
                'message' => 'Item không tồn tại trong thùng rác',
            ], 404);
        }

        $this->repository->forceDelete($id);

        return response()->json([
            'status' => true,
            'message' => 'Xóa vĩnh viễn thành công',
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn: ' . $e->getMessage(),
        ], 500);
    }
}
```

#### `bulkRestore(Request $request)`
Khôi phục nhiều records:

```php
/**
 * Bulk restore items
 */
public function bulkRestore(Request $request)
{
    try {
        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'Vui lòng chọn ít nhất một item',
            ], 400);
        }

        $count = $this->repository->bulkRestore($ids);

        return response()->json([
            'status' => true,
            'message' => "Đã khôi phục {$count} item thành công",
            'count' => $count,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Có lỗi xảy ra khi khôi phục: ' . $e->getMessage(),
        ], 500);
    }
}
```

#### `bulkForceDelete(Request $request)`
Xóa vĩnh viễn nhiều records:

```php
/**
 * Bulk force delete items
 */
public function bulkForceDelete(Request $request)
{
    try {
        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'Vui lòng chọn ít nhất một item',
            ], 400);
        }

        $count = $this->repository->bulkForceDelete($ids);

        return response()->json([
            'status' => true,
            'message' => "Đã xóa vĩnh viễn {$count} item thành công",
            'count' => $count,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn: ' . $e->getMessage(),
        ], 500);
    }
}
```

**Lưu ý:**
- Luôn kiểm tra `$item->trashed()` trước khi restore/force delete
- Sử dụng `withTrashed()` để tìm cả records đã bị soft delete
- Trả về JSON response với `status` và `message`

---

## 4. Routes Layer

### 4.1. Định nghĩa routes

Trong `routes/web.php`, thêm các routes sau vào group của module:

```php
Route::prefix('posts')->name('posts.')->group(function () {
    // Routes chính
    Route::get('/', [PostController::class, 'list'])->name('list');
    Route::get('/ajax-get-data', [PostController::class, 'ajaxGetData'])->name('ajaxGetData');
    
    // Routes cho thùng rác
    Route::get('/ajax-get-trashed-data', [PostController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData');
    Route::post('/restore/{id}', [PostController::class, 'restore'])->name('restore')->where('id', '[0-9]+');
    Route::delete('/force-delete/{id}', [PostController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+');
    
    // Routes cho bulk actions
    Route::post('/bulk-restore', [PostController::class, 'bulkRestore'])->name('bulkRestore');
    Route::delete('/bulk-force-delete', [PostController::class, 'bulkForceDelete'])->name('bulkForceDelete');
    
    // Routes CRUD khác
    Route::get('/create', [PostController::class, 'create'])->name('create');
    Route::post('/store', [PostController::class, 'store'])->name('store');
    // ...
});
```

**Lưu ý:**
- Route `restore` sử dụng method `POST` (không phải `PUT`)
- Route `forceDelete` sử dụng method `DELETE`
- Thêm constraint `->where('id', '[0-9]+')` cho các route có `{id}`

---

## 5. View Layer

### 5.1. Cấu trúc Tab

Trong file `list.blade.php`, thêm tab "Thùng rác" bên cạnh tab "Danh sách":

```blade
<div class="nav-align-top">
    <ul class="nav nav-tabs nav-fill" role="tablist">
        <li class="nav-item">
            <button type="button" class="nav-link rounded-0 border-0 active" role="tab" 
                data-bs-toggle="tab" data-bs-target="#list_tab" aria-controls="list_tab" 
                aria-selected="true">
                <i class="bx bx-list-ul me-1"></i> Danh sách
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link rounded-0 border-0" role="tab" 
                data-bs-toggle="tab" data-bs-target="#trash_tab" aria-controls="trash_tab" 
                aria-selected="false">
                <i class="bx bx-trash me-1"></i> Thùng rác
            </button>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- Tab Danh sách -->
        <div class="tab-pane fade show active" id="list_tab" role="tabpanel">
            <!-- DataTable chính -->
            <table class="table border-top" id="datatable_main">
                <!-- ... -->
            </table>
        </div>
        
        <!-- Tab Thùng rác -->
        <div class="tab-pane fade" id="trash_tab" role="tabpanel">
            <!-- Bulk Actions Container -->
            <div class="card-header border-bottom mb-3" id="bulkActionsContainer" style="display: none;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted" id="selectedCount">Đã chọn: <strong>0</strong> mục</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success btn-sm me-2" id="bulkRestoreBtn">
                            <i class="bx bx-undo me-1"></i> Khôi phục đã chọn
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="bulkForceDeleteBtn">
                            <i class="bx bx-trash me-1"></i> Xóa vĩnh viễn đã chọn
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- DataTable Thùng rác -->
            <div class="card-datatable table-responsive">
                <table class="table border-top" id="datatable_trash">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" class="form-check-input" id="selectAllTrash" />
                            </th>
                            <th>STT</th>
                            <th>Tiêu đề</th>
                            <!-- ... các cột khác -->
                            <th>Ngày xóa</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
```

### 5.2. Modals

Cần có các modals sau:

#### Restore Confirmation Modal (Single)
```blade
<div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-labelledby="confirmRestoreLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmRestoreLabel">Xác nhận khôi phục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn khôi phục "<strong id="restoreTitle"></strong>"?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-success" id="confirmRestoreBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    Khôi phục
                </button>
            </div>
        </div>
    </div>
</div>
```

#### Force Delete Confirmation Modal (Single)
```blade
<div class="modal fade" id="confirmForceDeleteModal" tabindex="-1" aria-labelledby="confirmForceDeleteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmForceDeleteLabel">Xác nhận xóa vĩnh viễn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <i class="bx bx-error-circle me-2"></i>
                    <strong>Cảnh báo:</strong> Bạn sắp xóa vĩnh viễn "<strong id="forceDeleteTitle"></strong>".<br>
                    Hành động này không thể hoàn tác và sẽ xóa vĩnh viễn khỏi hệ thống.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-danger" id="confirmForceDeleteBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    Xóa vĩnh viễn
                </button>
            </div>
        </div>
    </div>
</div>
```

#### Bulk Restore Modal
```blade
<div class="modal fade" id="bulkRestoreModal" tabindex="-1" aria-labelledby="bulkRestoreLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkRestoreLabel">Xác nhận khôi phục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-0">
                    <i class="bx bx-info-circle me-2"></i>
                    Bạn có chắc chắn muốn khôi phục <strong id="bulkRestoreCount">0</strong> item đã chọn?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-success" id="confirmBulkRestoreBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    Khôi phục
                </button>
            </div>
        </div>
    </div>
</div>
```

#### Bulk Force Delete Modal
```blade
<div class="modal fade" id="bulkForceDeleteModal" tabindex="-1" aria-labelledby="bulkForceDeleteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkForceDeleteLabel">Xác nhận xóa vĩnh viễn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <i class="bx bx-error-circle me-2"></i>
                    <strong>Cảnh báo:</strong> Bạn có chắc chắn muốn xóa vĩnh viễn <strong id="bulkForceDeleteCount">0</strong> item đã chọn?<br>
                    <small class="d-block mt-2">Hành động này không thể hoàn tác và sẽ xóa vĩnh viễn khỏi hệ thống.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-danger" id="confirmBulkForceDeleteBtn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    Xóa vĩnh viễn
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## 6. JavaScript Layer

### 6.1. Khai báo biến và DataTable

Trong file JavaScript của module (ví dụ: `blog.js`, `category.js`, `hashtag-crud.js`):

```javascript
// Khai báo biến để lưu selected IDs
let selectedIds = [];

// Khởi tạo DataTable cho thùng rác
window.moduleTrashTable = $('#datatable_trash').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
        url: "{{ route('admin.module.ajaxGetTrashedData') }}",
        data: function (d) {
            // Có thể thêm filter data ở đây
            d.status = $('#status').val();
        }
    },
    order: [[7, 'desc']], // Sắp xếp theo deleted_at (cột thứ 7)
    language: {
        url: "{{ asset_admin_url('assets/json/datatables/vi.json') }}",
        searchPlaceholder: "Tìm kiếm...",
    },
    columns: [
        { data: 'checkbox_html', name: 'checkbox', orderable: false, searchable: false, width: '50px' },
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'title_html', name: 'title' },
        // ... các cột khác
        { data: 'deleted_at_html', name: 'deleted_at', searchable: false },
        { data: 'action_html', name: 'action', orderable: false, searchable: false },
    ],
    drawCallback: function(settings) {
        // Reset select all checkbox khi table redraw
        $('#datatable_trash #selectAllTrash').prop('checked', false);
        selectedIds = [];
        $('#bulkActionsContainer').hide();
    }
});

// Reload trash table when tab is shown
$('button[data-bs-target="#trash_tab"]').on('shown.bs.tab', function () {
    window.moduleTrashTable.draw();
});
```

### 6.2. Checkbox Selection Logic

#### Chọn tất cả
```javascript
$(document).on('change', '#datatable_trash #selectAllTrash', function() {
    const isChecked = $(this).is(':checked');
    $('#datatable_trash tbody .row-checkbox').prop('checked', isChecked);
    updateSelectedIds();
});
```

#### Chọn từng item
```javascript
$(document).on('change', '#datatable_trash tbody .row-checkbox', function() {
    updateSelectedIds();
    // Update select all checkbox
    const totalCheckboxes = $('#datatable_trash tbody .row-checkbox').length;
    const checkedCheckboxes = $('#datatable_trash tbody .row-checkbox:checked').length;
    $('#datatable_trash #selectAllTrash').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
});
```

#### Update selected IDs
```javascript
function updateSelectedIds() {
    selectedIds = [];
    $('#datatable_trash tbody .row-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });

    const count = selectedIds.length;
    $('#selectedCount strong').text(count);

    if (count > 0) {
        $('#bulkActionsContainer').slideDown();
    } else {
        $('#bulkActionsContainer').slideUp();
    }
}
```

### 6.3. Single Restore Handler

```javascript
let restoreUrl = null;
let currentTrashRow = null;

// Khi click nút restore
$(document).on("click", ".btn-restore", function () {
    restoreUrl = $(this).data("url");
    const title = $(this).data("title");
    currentTrashRow = $(this).closest("tr");

    $("#restoreTitle").text(title || "item này");
    const modal = new bootstrap.Modal($("#confirmRestoreModal"));
    modal.show();
});

// Khi nhấn nút "Khôi phục"
$("#confirmRestoreBtn").on("click", function () {
    if (!restoreUrl) {
        toastr.error("Không tìm thấy URL khôi phục.", "Thông báo");
        return;
    }

    const btn = $(this);
    const spinner = btn.find(".spinner-border");

    btn.prop("disabled", true);
    spinner.removeClass("d-none");

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        url: restoreUrl,
        type: "POST",
        success: function (res) {
            if (res.status) {
                $("#confirmRestoreModal").modal("hide");

                // Reload cả bảng danh sách chính và bảng thùng rác
                if (typeof window.moduleTable !== 'undefined') {
                    window.moduleTable.draw();
                }
                if (typeof window.moduleTrashTable !== 'undefined') {
                    window.moduleTrashTable.draw();
                }

                toastr.success(res.message || "Khôi phục thành công", "Thông báo");
            } else {
                toastr.error(res.message || "Không thể khôi phục", "Thông báo");
            }
        },
        error: function (xhr) {
            let message = "Lỗi khi khôi phục";
            if (xhr.responseJSON) {
                message = xhr.responseJSON.message || message;
            }
            toastr.error(message, "Thông báo");
        },
        complete: function () {
            btn.prop("disabled", false);
            spinner.addClass("d-none");
        },
    });
});
```

### 6.4. Single Force Delete Handler

```javascript
let forceDeleteUrl = null;

// Khi click nút force delete
$(document).on("click", ".btn-force-delete", function () {
    forceDeleteUrl = $(this).data("url");
    const title = $(this).data("title");
    currentTrashRow = $(this).closest("tr");

    $("#forceDeleteTitle").text(title || "item này");
    const modal = new bootstrap.Modal($("#confirmForceDeleteModal"));
    modal.show();
});

// Khi nhấn nút "Xóa vĩnh viễn"
$("#confirmForceDeleteBtn").on("click", function () {
    if (!forceDeleteUrl) {
        toastr.error("Không tìm thấy URL xóa.", "Thông báo");
        return;
    }

    const btn = $(this);
    const spinner = btn.find(".spinner-border");

    btn.prop("disabled", true);
    spinner.removeClass("d-none");

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        url: forceDeleteUrl,
        type: "DELETE",
        success: function (res) {
            if (res.status) {
                $("#confirmForceDeleteModal").modal("hide");

                if (typeof window.moduleTrashTable !== 'undefined') {
                    window.moduleTrashTable.draw();
                }

                toastr.success(res.message || "Xóa vĩnh viễn thành công", "Thông báo");
            } else {
                toastr.error(res.message || "Không thể xóa vĩnh viễn", "Thông báo");
            }
        },
        error: function (xhr) {
            let message = "Lỗi khi xóa vĩnh viễn";
            if (xhr.responseJSON) {
                message = xhr.responseJSON.message || message;
            }
            toastr.error(message, "Thông báo");
        },
        complete: function () {
            btn.prop("disabled", false);
            spinner.addClass("d-none");
        },
    });
});
```

### 6.5. Bulk Actions Handlers

#### Bulk Restore
```javascript
// Định nghĩa route URL trong Blade view
// window.moduleBulkRestoreUrl = "{{ route('admin.module.bulkRestore') }}";

$(document).on('click', '#bulkRestoreBtn', function() {
    if (selectedIds.length === 0) {
        toastr.warning('Vui lòng chọn ít nhất một item', 'Thông báo');
        return;
    }

    $('#bulkRestoreCount').text(selectedIds.length);
    const modal = new bootstrap.Modal($('#bulkRestoreModal'));
    modal.show();
});

$(document).on('click', '#confirmBulkRestoreBtn', function() {
    const btn = $(this);
    const spinner = btn.find('.spinner-border');
    const originalHtml = btn.html();

    btn.prop('disabled', true);
    spinner.removeClass('d-none');

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        url: window.moduleBulkRestoreUrl || '/admin/module/bulk-restore',
        type: 'POST',
        data: { ids: selectedIds },
        success: function(res) {
            $('#bulkRestoreModal').modal('hide');
            if (res.status) {
                toastr.success(res.message, 'Thông báo');
                // Reload cả bảng danh sách chính và bảng thùng rác
                if (typeof window.moduleTable !== 'undefined') {
                    window.moduleTable.draw();
                }
                reloadModuleTrashTable();
            } else {
                toastr.error(res.message, 'Thông báo');
            }
        },
        error: function(xhr) {
            let message = 'Lỗi khi khôi phục';
            if (xhr.responseJSON) {
                message = xhr.responseJSON.message || message;
            }
            toastr.error(message, 'Thông báo');
        },
        complete: function() {
            btn.prop('disabled', false);
            spinner.addClass('d-none');
        }
    });
});
```

#### Bulk Force Delete
```javascript
// Định nghĩa route URL trong Blade view
// window.moduleBulkForceDeleteUrl = "{{ route('admin.module.bulkForceDelete') }}";

$(document).on('click', '#bulkForceDeleteBtn', function() {
    if (selectedIds.length === 0) {
        toastr.warning('Vui lòng chọn ít nhất một item', 'Thông báo');
        return;
    }

    $('#bulkForceDeleteCount').text(selectedIds.length);
    const modal = new bootstrap.Modal($('#bulkForceDeleteModal'));
    modal.show();
});

$(document).on('click', '#confirmBulkForceDeleteBtn', function() {
    const btn = $(this);
    const spinner = btn.find('.spinner-border');
    const originalHtml = btn.html();

    btn.prop('disabled', true);
    spinner.removeClass('d-none');

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        url: window.moduleBulkForceDeleteUrl || '/admin/module/bulk-force-delete',
        type: 'DELETE',
        data: { ids: selectedIds },
        success: function(res) {
            $('#bulkForceDeleteModal').modal('hide');
            if (res.status) {
                toastr.success(res.message, 'Thông báo');
                reloadModuleTrashTable();
            } else {
                toastr.error(res.message, 'Thông báo');
            }
        },
        error: function(xhr) {
            let message = 'Lỗi khi xóa vĩnh viễn';
            if (xhr.responseJSON) {
                message = xhr.responseJSON.message || message;
            }
            toastr.error(message, 'Thông báo');
        },
        complete: function() {
            btn.prop('disabled', false);
            spinner.addClass('d-none');
        }
    });
});
```

#### Reload Trash Table Function
```javascript
function reloadModuleTrashTable() {
    if (typeof window.moduleTrashTable !== 'undefined') {
        window.moduleTrashTable.draw();
        selectedIds = [];
        $('#datatable_trash #selectAllTrash').prop('checked', false);
        $('#bulkActionsContainer').slideUp();
    }
}
```

### 6.6. Reset khi chuyển tab

```javascript
$(document).on('shown.bs.tab', 'button[data-bs-target="#trash_tab"]', function () {
    selectedIds = [];
    $('#datatable_trash #selectAllTrash').prop('checked', false);
    $('#bulkActionsContainer').hide();
});
```

---

## 7. Định nghĩa Route URLs trong Blade View

Vì JavaScript được load qua Vite, cần định nghĩa route URLs trong inline script:

```blade
@push('scripts')
    @vite(['resources/js/admin/pages/module.js'])
    <script>
        // Define routes for bulk actions
        window.moduleBulkRestoreUrl = "{{ route('admin.module.bulkRestore') }}";
        window.moduleBulkForceDeleteUrl = "{{ route('admin.module.bulkForceDelete') }}";
    </script>
@endpush
```

---

## 8. Delete Confirmation Message (Main List)

Trong tab "Danh sách", khi xóa một item, modal confirmation cần thông báo rõ ràng rằng item sẽ được chuyển vào thùng rác:

```blade
<div class="modal-body">
    <div class="alert alert-warning mb-0">
        <i class="bx bx-info-circle me-2"></i>
        <strong>Thông báo:</strong> Bạn sắp xóa item "<strong id="deleteTitle"></strong>".<br>
        Item sẽ được chuyển vào <strong>Thùng rác</strong> và bạn có thể khôi phục lại sau.
    </div>
</div>
```

---

## 9. Checklist khi triển khai cho module mới

### Model
- [ ] Import `SoftDeletes` trait
- [ ] Thêm `use SoftDeletes;` trong model
- [ ] Migration có cột `deleted_at` (timestamp, nullable)

### Repository
- [ ] Implement `gridTrashedData()`
- [ ] Implement `renderTrashedDataTables($data)` với `checkbox_html` column
- [ ] Implement `restore($id)`
- [ ] Implement `forceDelete($id)`
- [ ] Implement `bulkRestore(array $ids)`
- [ ] Implement `bulkForceDelete(array $ids)`

### Controller
- [ ] Implement `ajaxGetTrashedData()`
- [ ] Implement `restore($id)` với validation
- [ ] Implement `forceDelete($id)` với validation
- [ ] Implement `bulkRestore(Request $request)`
- [ ] Implement `bulkForceDelete(Request $request)`

### Routes
- [ ] Route `ajax-get-trashed-data` (GET)
- [ ] Route `restore/{id}` (POST) với constraint
- [ ] Route `force-delete/{id}` (DELETE) với constraint
- [ ] Route `bulk-restore` (POST)
- [ ] Route `bulk-force-delete` (DELETE)

### Views
- [ ] Tab "Thùng rác" với icon `bx-trash`
- [ ] Bulk actions container với 2 buttons
- [ ] DataTable cho thùng rác với checkbox column
- [ ] Modal "Restore Confirmation" (single)
- [ ] Modal "Force Delete Confirmation" (single)
- [ ] Modal "Bulk Restore Confirmation"
- [ ] Modal "Bulk Force Delete Confirmation"
- [ ] Update delete confirmation message trong tab "Danh sách"

### JavaScript
- [ ] Khai báo `selectedIds = []`
- [ ] Khởi tạo DataTable cho trash với `drawCallback`
- [ ] Event handler cho `#selectAllTrash`
- [ ] Event handler cho `.row-checkbox`
- [ ] Function `updateSelectedIds()`
- [ ] Function `reloadModuleTrashTable()`
- [ ] Handler cho single restore
- [ ] Handler cho single force delete
- [ ] Handler cho bulk restore
- [ ] Handler cho bulk force delete
- [ ] Reset khi chuyển tab
- [ ] Định nghĩa route URLs trong Blade view

---

## 10. Lưu ý quan trọng

1. **Soft Delete vs Hard Delete:**
   - Xóa từ tab "Danh sách" → Soft delete (chuyển vào thùng rác)
   - Xóa từ tab "Thùng rác" → Force delete (xóa vĩnh viễn)

2. **Reload Tables:**
   - Sau khi restore, **phải reload cả 2 bảng**: bảng danh sách chính và bảng thùng rác
   - Sau khi force delete, chỉ cần reload bảng thùng rác

3. **Validation:**
   - Luôn kiểm tra `$item->trashed()` trước khi restore/force delete
   - Trả về error message rõ ràng nếu item không tồn tại hoặc không ở trong thùng rác

4. **Bulk Actions:**
   - Sử dụng Bootstrap Modal thay vì `confirm()` để UX tốt hơn
   - Hiển thị số lượng items được chọn trong modal

5. **Checkbox Selection:**
   - Sử dụng event delegation `$(document).on()` để xử lý dynamic content từ DataTable
   - Reset checkbox state trong `drawCallback`

6. **Route URLs:**
   - Định nghĩa route URLs trong Blade view (inline script) vì JavaScript được load qua Vite
   - Sử dụng `window.moduleBulkRestoreUrl` và `window.moduleBulkForceDeleteUrl`

---

## 11. Ví dụ hoàn chỉnh

Xem các file sau để tham khảo implementation đầy đủ:

- **Model**: `app/Models/Post.php`, `app/Models/Category.php`, `app/Models/HashTag.php`
- **Repository**: `app/Repositories/PostRepository.php` (methods từ line 220+)
- **Controller**: `app/Http/Controllers/Admin/PostController.php` (methods từ line 80+)
- **Routes**: `routes/web.php` (group `posts`, `categories`, `hashtags`)
- **Views**: `resources/views/admin/modules/post/list.blade.php`
- **JavaScript**: `resources/js/admin/pages/blog.js` (từ line 350+)

---

## 12. Troubleshooting

### Checkbox không hoạt động
- Kiểm tra selector có đúng không
- Đảm bảo event handler được bind sau khi DataTable khởi tạo
- Kiểm tra `drawCallback` có reset checkbox state không

### Bulk actions không hoạt động
- Kiểm tra route URLs có được định nghĩa trong Blade view không
- Kiểm tra `selectedIds` có được khai báo đúng scope không
- Kiểm tra CSRF token có được set trong AJAX request không

### Restore không reload bảng chính
- Đảm bảo sau khi restore thành công, gọi `window.moduleTable.draw()`
- Kiểm tra `window.moduleTable` có được khai báo global không

---

**Tài liệu này được tạo để hỗ trợ AI và developers trong việc triển khai tính năng Soft Delete & Restore cho các module mới.**
