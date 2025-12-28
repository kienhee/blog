# Test Checklist - Chức năng Đổi Mật Khẩu

## Test cho Client Profile (`/profile/doi-mat-khau`)

### 1. Test Validation Frontend

#### 1.1. Mật khẩu hiện tại (currentPassword)
- [1] **TC001**: Bỏ trống mật khẩu hiện tại → Hiển thị lỗi "Vui lòng nhập mật khẩu hiện tại" 
- [1] **TC002**: Nhập mật khẩu hiện tại sai → Submit → Backend trả về lỗi "Mật khẩu hiện tại không chính xác"
- [1] **TC003**: Nhập mật khẩu hiện tại đúng → Validation pass

#### 1.2. Mật khẩu mới (newPassword)
- [ 1] **TC004**: Bỏ trống mật khẩu mới → Hiển thị lỗi "Vui lòng nhập mật khẩu mới"
- [ 1] **TC005**: Nhập mật khẩu mới < 6 ký tự (vd: "12345") → Hiển thị lỗi "Mật khẩu mới phải từ 6 đến 255 ký tự"
- [1 ] **TC006**: Nhập mật khẩu mới = 6 ký tự (vd: "123456") → Validation pass
- [1 ] **TC007**: Nhập mật khẩu mới > 255 ký tự → Hiển thị lỗi
- [ 1] **TC008**: Nhập mật khẩu mới giống mật khẩu hiện tại → Hiển thị lỗi "Mật khẩu mới phải khác mật khẩu hiện tại"

#### 1.3. Xác nhận mật khẩu (newPassword_confirmation)
- [ 1] **TC009**: Bỏ trống xác nhận mật khẩu → Hiển thị lỗi "Vui lòng xác nhận mật khẩu mới"
- [ ] **TC010**: Nhập xác nhận mật khẩu không khớp với mật khẩu mới → Hiển thị lỗi "Mật khẩu xác nhận không khớp"
- [ ] **TC011**: Nhập xác nhận mật khẩu khớp với mật khẩu mới → Validation pass

### 2. Test Validation Backend

- [ ] **TC012**: Submit form với dữ liệu hợp lệ → Kiểm tra backend validation
- [ ] **TC013**: Gửi request với mật khẩu hiện tại sai → Backend trả về 422 với lỗi
- [ ] **TC014**: Gửi request với mật khẩu mới < 6 ký tự → Backend trả về 422
- [ ] **TC015**: Gửi request với mật khẩu mới = mật khẩu cũ → Backend trả về 422 với lỗi "different"

### 3. Test AJAX Submit

- [ ] **TC016**: Submit form hợp lệ → Không reload trang, hiển thị toast success "Đổi mật khẩu thành công!"
- [ ] **TC017**: Submit form có lỗi → Hiển thị toast error với message lỗi
- [ ] **TC018**: Submit form → Button bị disable và hiển thị spinner trong lúc xử lý
- [ ] **TC019**: Sau khi thành công → Form được reset tự động

### 4. Test Toast Notification

- [ ] **TC020**: Toast success hiển thị ở góc trên bên phải
- [ ] **TC021**: Toast tự động ẩn sau 5 giây
- [ ] **TC022**: Toast có nút đóng (close button)
- [ ] **TC023**: Toast có progress bar

### 5. Test Chức năng

- [ ] **TC024**: Đổi mật khẩu thành công → Đăng xuất và đăng nhập lại với mật khẩu mới → Thành công
- [ ] **TC025**: Đổi mật khẩu thành công → Đăng nhập với mật khẩu cũ → Thất bại
- [ ] **TC026**: Reset form → Tất cả các field được clear, validation errors được xóa

---

## Test cho Admin Profile (`/admin/users/profile` - Tab Đổi mật khẩu)

### Lặp lại tất cả các test case trên cho Admin Profile

- [ ] **TC027-TC051**: Lặp lại TC001-TC026 cho admin profile

### Test riêng cho Admin

- [ ] **TC052**: Mở tab "Đổi mật khẩu" → Form hiển thị đúng
- [ ] **TC053**: Có lỗi validation → Tự động mở tab "Đổi mật khẩu"
- [ ] **TC054**: URL có hash `#password-tab` → Tự động mở tab "Đổi mật khẩu"

---

## Test Cross-browser

- [ ] **TC055**: Test trên Chrome
- [ ] **TC056**: Test trên Firefox
- [ ] **TC057**: Test trên Safari/Edge

---

## Test Responsive

- [ ] **TC058**: Test trên mobile (< 768px)
- [ ] **TC059**: Test trên tablet (768px - 1024px)
- [ ] **TC060**: Test trên desktop (> 1024px)

---

## Các trường hợp Edge Case

- [ ] **TC061**: Submit form nhiều lần liên tiếp → Chỉ xử lý 1 request
- [ ] **TC062**: Đổi mật khẩu khi session hết hạn → Xử lý đúng
- [ ] **TC063**: Copy/paste mật khẩu → Validation vẫn hoạt động
- [ ] **TC064**: Nhập mật khẩu có ký tự đặc biệt → Hoạt động bình thường
- [ ] **TC065**: Nhập mật khẩu có emoji → Xử lý đúng

---

## Checklist nhanh để test nhanh

### Test cơ bản (5 phút):
1. [ ] Bỏ trống mật khẩu hiện tại → Có lỗi
2. [ ] Nhập mật khẩu hiện tại sai → Có lỗi
3. [ ] Nhập mật khẩu mới < 6 ký tự → Có lỗi
4. [ ] Nhập mật khẩu mới = mật khẩu cũ → Có lỗi
5. [ ] Nhập xác nhận mật khẩu không khớp → Có lỗi
6. [ ] Nhập đúng tất cả → Thành công, có toast, form reset

### Test đầy đủ (15 phút):
- Chạy tất cả test case từ TC001-TC026 cho client
- Chạy tất cả test case từ TC027-TC051 cho admin

---

## Ghi chú khi test

1. **Test với user thật**: Đảm bảo có user test với mật khẩu đã biết
2. **Test cả 2 môi trường**: Client và Admin
3. **Kiểm tra console**: Không có lỗi JavaScript
4. **Kiểm tra Network tab**: AJAX request trả về đúng status code
5. **Kiểm tra database**: Mật khẩu được hash đúng sau khi đổi

