# Checklist Test Module Contact

## 📋 Tổng quan

Module Contact quản lý các liên hệ từ khách hàng với 4 trạng thái:

-   **0: Chưa xử lý** (mặc định khi có contact mới)
-   **1: Đã liên hệ** (khi liên hệ qua điện thoại)
-   **2: Đã trả lời email** (tự động khi gửi email)
-   **3: Spam** (khi đánh dấu là spam)

---

## ✅ Test Cases

### 1. Test Hiển thị Danh sách Contact

#### 1.1. Load trang danh sách

-   [ ] Truy cập `/admin/contacts`
-   [ ] Kiểm tra DataTable hiển thị đúng các cột:
    -   STT
    -   Họ tên
    -   Email
    -   Chủ đề
    -   Tin nhắn
    -   Trạng thái (badge màu)
    -   Ngày tạo
    -   Hành động (2 icon: xem, edit)
-   [ ] Kiểm tra pagination hoạt động
-   [ ] Kiểm tra sắp xếp theo cột "Ngày tạo" (mới nhất trước)

#### 1.2. Kiểm tra Badge trạng thái

-   [ ] Trạng thái "Chưa xử lý" → badge màu vàng (warning)
-   [ ] Trạng thái "Đã liên hệ" → badge màu xanh dương (info)
-   [ ] Trạng thái "Đã trả lời email" → badge màu xanh lá (success)
-   [ ] Trạng thái "Spam" → badge màu đỏ (danger)

---

### 2. Test Filter/Search

#### 2.1. Filter theo Họ tên

-   [ ] Nhập họ tên → DataTable filter đúng
-   [ ] Xóa filter → hiển thị lại tất cả
-   [ ] Test với ký tự đặc biệt, tiếng Việt

#### 2.2. Filter theo Email

-   [ ] Nhập email → DataTable filter đúng
-   [ ] Test partial match (tìm một phần email)

#### 2.3. Filter theo Chủ đề

-   [ ] Nhập chủ đề → DataTable filter đúng

#### 2.4. Filter theo Trạng thái

-   [ ] Chọn "Chưa xử lý" → chỉ hiển thị contact status = 0
-   [ ] Chọn "Đã liên hệ" → chỉ hiển thị contact status = 1
-   [ ] Chọn "Đã trả lời email" → chỉ hiển thị contact status = 2
-   [ ] Chọn "Spam" → chỉ hiển thị contact status = 3
-   [ ] Chọn "Tất cả" → hiển thị tất cả

#### 2.5. Filter theo Ngày tạo

-   [ ] Chọn ngày → DataTable filter đúng
-   [ ] Format: DD/MM/YYYY

#### 2.6. Nút "Đặt lại"

-   [ ] Click "Đặt lại" → tất cả filter về rỗng
-   [ ] DataTable reload hiển thị tất cả

---

### 3. Test Xem Chi tiết Contact

#### 3.1. Mở Modal chi tiết

-   [ ] Click icon "Xem chi tiết" (mắt) → Modal mở
-   [ ] Kiểm tra loading spinner hiển thị khi đang tải
-   [ ] Kiểm tra modal size: `modal-xl`

#### 3.2. Tab "Thông tin"

-   [ ] Hiển thị đầy đủ thông tin:
    -   Họ tên
    -   Email (có link mailto)
    -   Chủ đề
    -   Trạng thái (badge)
    -   Tin nhắn (có scroll nếu dài)
    -   Ngày tạo
    -   Cập nhật lần cuối

#### 3.3. Tab "Lịch sử trả lời"

-   [ ] Hiển thị timeline các reply (nếu có)
-   [ ] Reply mới nhất có border màu primary
-   [ ] Hiển thị: Subject, Người trả lời, Thời gian, Nội dung
-   [ ] Nếu chưa có reply → hiển thị alert "Chưa có phản hồi nào"

#### 3.4. Form "Trả lời nhanh"

-   [ ] Subject tự động điền "Re: [Chủ đề gốc]"
-   [ ] Textarea message có placeholder
-   [ ] Validation: Subject và Message bắt buộc
-   [ ] Validation: Message tối thiểu 10 ký tự

---

### 4. Test Trả lời Email

#### 4.1. Gửi reply thành công

-   [ ] Điền đầy đủ Subject và Message (≥10 ký tự)
-   [ ] Click "Gửi trả lời"
-   [ ] Kiểm tra:
    -   Toastr success message hiển thị
    -   Email được gửi đến khách hàng
    -   Reply được lưu vào database (`contact_replies`)
    -   Modal tự động reload hiển thị reply mới
    -   Tự động chuyển sang tab "Lịch sử trả lời"
    -   Form được reset (giữ lại subject với "Re:")

#### 4.2. Logic tự động cập nhật trạng thái

-   [ ] Contact status = "Chưa xử lý" → sau khi reply → tự động chuyển thành "Đã trả lời email"
-   [ ] Contact status = "Đã liên hệ" → sau khi reply → tự động chuyển thành "Đã trả lời email"
-   [ ] Contact status = "Đã trả lời email" → sau khi reply → vẫn giữ nguyên
-   [ ] Contact status = "Spam" → sau khi reply → vẫn giữ nguyên

#### 4.3. Validation errors

-   [ ] Bỏ trống Subject → hiển thị lỗi
-   [ ] Bỏ trống Message → hiển thị lỗi
-   [ ] Message < 10 ký tự → hiển thị lỗi
-   [ ] Lỗi hiển thị bằng toastr

#### 4.4. Email không gửi được

-   [ ] Simulate lỗi gửi email (sai config mail)
-   [ ] Kiểm tra: Reply vẫn được lưu vào DB
-   [ ] Kiểm tra: Error được log vào file log
-   [ ] Transaction không bị rollback

---

### 5. Test Thay đổi Trạng thái

#### 5.1. Mở Modal thay đổi trạng thái

-   [ ] Click icon "Edit" (bút chì) → Modal mở
-   [ ] Modal hiển thị 4 radio buttons:
    -   Chưa xử lý (icon message, màu vàng)
    -   Đã liên hệ (icon phone, màu xanh dương)
    -   Đã trả lời email (icon envelope, màu xanh lá)
    -   Spam (icon shield, màu đỏ)
-   [ ] Trạng thái hiện tại được check (có icon check bên phải)

#### 5.2. Thay đổi trạng thái

-   [ ] Chọn trạng thái khác → Click "Xác nhận"
-   [ ] Kiểm tra:
    -   Toastr success message
    -   DataTable tự động reload
    -   Badge trạng thái cập nhật đúng
    -   Icon trong cột action không thay đổi (vì đã xóa)

#### 5.3. Validation

-   [ ] Không chọn trạng thái nào → Click "Xác nhận" → Hiển thị lỗi
-   [ ] Chọn trạng thái không hợp lệ (qua URL) → Trả về 400 error

---

### 6. Test Edge Cases & Error Handling

#### 6.1. Contact không tồn tại

-   [ ] Truy cập `/admin/contacts/99999` → 404 error
-   [ ] Thử reply contact không tồn tại → Error message

#### 6.2. Dữ liệu lớn

-   [ ] Contact có message rất dài → Hiển thị đúng với scroll
-   [ ] Subject rất dài → Hiển thị tooltip khi hover

#### 6.3. XSS Protection

-   [ ] Tạo contact với HTML/JavaScript trong message
-   [ ] Kiểm tra: Không bị execute code, chỉ hiển thị text

#### 6.4. CSRF Protection

-   [ ] Thử submit form không có CSRF token → 419 error

---

### 7. Test Database & Seeder

#### 7.1. Seeder

-   [ ] Chạy `php artisan db:seed --class=ContactSeeder`
-   [ ] Kiểm tra: Tạo được 100 contacts
-   [ ] Kiểm tra phân bố trạng thái:
    -   ~40% Chưa xử lý
    -   ~20% Đã liên hệ
    -   ~35% Đã trả lời email
    -   ~5% Spam

#### 7.2. Migration

-   [ ] Kiểm tra comment cột `status` trong database:
    ```sql
    SHOW FULL COLUMNS FROM contacts;
    ```
    -   Comment phải là: `0: Chưa xử lý, 1: Đã liên hệ, 2: Đã trả lời email, 3: Spam`

---

### 8. Test UI/UX

#### 8.1. Responsive

-   [ ] Test trên mobile → Modal, table responsive
-   [ ] Test trên tablet → Layout hiển thị đúng

#### 8.2. Loading states

-   [ ] Spinner hiển thị khi đang load data
-   [ ] Button disabled khi đang submit

#### 8.3. Tooltips

-   [ ] Hover vào icon → Tooltip hiển thị đúng text
-   [ ] Hover vào subject/message ngắn → Tooltip hiển thị full text

---

## 🧪 Cách Test Nhanh

### Test Flow Cơ bản:

1. **Tạo contact mới** (qua form public hoặc seeder)
2. **Kiểm tra** contact xuất hiện với status "Chưa xử lý"
3. **Filter** theo trạng thái "Chưa xử lý"
4. **Xem chi tiết** → Kiểm tra thông tin đầy đủ
5. **Trả lời email** → Kiểm tra:
    - Status tự động chuyển thành "Đã trả lời email"
    - Reply xuất hiện trong timeline
6. **Thay đổi trạng thái** → Chọn "Đã liên hệ"
7. **Kiểm tra** badge và DataTable cập nhật

### Test với Seeder:

```bash
# Tạo dữ liệu test
php artisan db:seed --class=ContactSeeder

# Xem trong database
php artisan tinker
>>> \App\Models\Contact::count()
>>> \App\Models\Contact::groupBy('status')->selectRaw('status, count(*) as count')->get()
```

---

## 📝 Notes

-   **Email Testing**: Cần cấu hình mail driver (mailtrap, log, etc.) để test gửi email
-   **Performance**: Test với 1000+ contacts để kiểm tra pagination
-   **Browser**: Test trên Chrome, Firefox, Safari

---

## ✅ Checklist Hoàn thành

Sau khi test xong, đánh dấu các mục đã test và ghi chú nếu có lỗi:

-   [ ] Tất cả test cases đã pass
-   [ ] Không có lỗi console/network
-   [ ] UI/UX hoạt động mượt mà
-   [ ] Logic business đúng theo yêu cầu
