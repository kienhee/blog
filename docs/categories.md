## Danh mục - Quy trình

### Cây danh mục (jsTree)

-   Một cây duy nhất cho danh mục bài viết.
-   Tự mở tất cả node; node vô hiệu hóa có màu nhạt và cursor cấm (trong cây chọn cha).
-   Chỉ click vào node jsTree mới chuyển đến trang sửa; hàng DataTable không điều hướng.

### Chọn danh mục cha (Tạo/Sửa)

-   Tải toàn bộ danh sách cha (không phân loại).
-   Vô hiệu hóa: danh mục hiện tại và tất cả con cháu (hiển thị nhưng không chọn được).
-   Mặc định: không chọn thì là danh mục cha (cấp cao nhất).

### Kéo thả (Sắp xếp + đổi cha)

-   Bật plugin dnd.
-   Client chặn cơ bản (không cho làm cha của chính nó).
-   Server validate:
    -   `id` tồn tại
    -   `parent_id` (nullable) tồn tại, không phải chính nó, không phải hậu duệ
    -   `position` (0-based) dùng để tính thứ tự ổn định
-   Khi thả:
    -   Cập nhật `parent_id`
    -   Tính `order` theo `position`
    -   Chuẩn hóa thứ tự anh em về 0..N

### Trường sắp xếp

-   Cột DB: `order` (int, mặc định 0).
-   Cây và danh sách sắp theo: parent null trước, sau đó `parent_id`, `order`, `created_at`.

### Xóa danh mục

-   Một modal xác nhận thống nhất:
    -   Hiển thị toàn bộ cây con của danh mục mục tiêu
    -   Hiển thị số liệu:
        -   Tổng số danh mục con (mọi cấp)
        -   Số bài viết/sản phẩm trực tiếp (không tính con)
    -   Cảnh báo:
        -   Con trực tiếp sẽ được đưa lên cha gần nhất của danh mục bị xóa
        -   Tất cả bài viết/sản phẩm thuộc cây sẽ chuyển vào danh mục “Chưa phân loại” tương ứng
-   Danh mục hệ thống (không xóa, ẩn nút xóa):
    -   ID 9999: “Chưa phân loại” (blog)
-   Luồng server khi xóa:
    -   Có con trực tiếp: cập nhật `parent_id` của con trực tiếp về cha của danh mục bị xóa
    -   Tính danh sách ID bị ảnh hưởng (chính nó + tất cả con cháu)
    -   Chuyển toàn bộ bài viết sang danh mục “Chưa phân loại”
    -   Xóa danh mục

### Danh mục “Chưa phân loại”

-   getOrCreateUncategorizedCategory():
    -   ID 9999, tên “Chưa phân loại”, slug `chua-phan-loai`
    -   Nếu bị soft-delete sẽ restore; đảm bảo field nhất quán.

### API/Routes

-   POST `/admin/categories/update-order` → cập nhật `parent_id` + `order`
-   GET `/admin/categories/delete-info/{id}` → dữ liệu cho modal xóa (cây, số liệu)
-   DELETE `/admin/categories/destroy/{id}` → xóa + đổi cha + chuyển “Chưa phân loại”

### Ghi chú frontend

-   JS: `resources/js/admin/pages/category.js`
-   Dùng jQuery + Bootstrap Modal + Toastr
-   Gửi CSRF/headers đầy đủ; xử lý lỗi/rollback (refresh jsTree khi lỗi)
