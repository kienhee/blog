# Media Library — Feature Plan

Tài liệu này liệt kê các chức năng nên có, ưu tiên theo giai đoạn, để triển khai module Media library cho hệ thống blog (Spring Boot + Thymeleaf + MySQL). Checklist bên dưới phản ánh tiến độ thực tế — cập nhật dấu tick mỗi khi làm thêm phần nào.

## Checklist tiến độ

**Tích hợp Avatar (Profile) — đã làm**
- [x] User entity thêm `avatarUrl` (`V10__User_avatar.sql`); "Replace photo" trong Profile giờ upload thật, không còn là nút mock
- [x] Ảnh avatar tự động lưu vào folder **"Avatars"** trong Media library — folder tự tạo nếu chưa có (`MediaFolderService.getOrCreateByName`), tái dùng thẳng `MediaService.uploadMedia(file, email, folderId)` sẵn có, không phải viết luồng upload riêng
- [x] Chỉ nhận ảnh (chặn PDF/DOC/... ở tầng controller trước khi gọi service)
- [x] Avatar hiển thị thật ở cả trang Profile lẫn sidebar (trước đó là khối màu placeholder)
- [x] **Bảo vệ nhất quán với cover ảnh bài viết**: thêm `UserRepository.existsByAvatarUrl` + chặn xoá media nếu đang là avatar của user nào đó (giống hệt cơ chế đã có cho `Post.coverImage`) — badge "Used"/"Unused" trong Media library giờ cũng tính luôn avatar, không chỉ cover bài viết
- [x] Test thật: upload avatar → xác nhận file nằm đúng folder "Avatars" → xoá file đang là avatar bị chặn đúng lỗi → đổi avatar khác → avatar cũ xoá được bình thường

**Sự cố trong lúc test (đã tự phát hiện và vá):** lúc đầu tính năng bảo vệ này *chưa tồn tại* — mình xoá thử 1 avatar test và vô tình làm avatar thật của tài khoản admin trỏ tới ảnh đã xoá (broken link). Phát hiện ngay, bổ sung luôn cơ chế chặn ở trên, rồi khắc phục bằng cách gán lại avatar hợp lệ cho admin. Không có dữ liệu nào khác bị ảnh hưởng.

**Giao diện kiểu File Explorer thật (Windows/macOS-style) — đã làm**
- [x] Sidebar cây thư mục ("Home" + các thư mục đã tạo) **và** folder xuất hiện như icon điều hướng được ngay trong lưới nội dung — click/vào folder cập nhật breadcrumb, chỉ hiện file thuộc đúng folder đang xem (folder không lồng nhau)
- [x] Breadcrumb "Home / Tên thư mục" ở đầu vùng nội dung
- [x] Toolbar trên cùng: **New Folder**, **Upload**, nút **Refresh**
- [x] Upload khi đang đứng trong 1 thư mục → file mới tự động nằm trong thư mục đó (`uploadMedia` nhận thêm `folderId`, JS bật/tắt hidden field theo `currentFolder` mỗi lần điều hướng — tắt hẳn field thay vì để rỗng, vì `""` không convert được sang `Long` ở Spring) — test thật: upload kèm `folderId` → file vào đúng thư mục; upload không kèm (ở Home) → file unfiled đúng như trước
- [x] Click 1 file = chọn (hiện checkbox góc trên trái) — **thanh hành động thay hẳn thanh filter** khi có file được chọn: **Rename** (đúng 1 file), **Move to** (dropdown thư mục), **Download**, **Delete**, đếm số lượng + nút X bỏ chọn
- [x] Double-click file = mở panel chi tiết
- [x] Chuột phải vào thư mục (sidebar lẫn icon trong grid): Open / Rename (popover) / View info (toast) / Delete
- [x] Chuột phải vào vùng trống: New folder
- [x] Chuột phải vào file: Open / Rename / **Move to** (liệt kê từng thư mục) / **Download** / Delete — đều kèm icon giống bản tham khảo
- [x] File không phải ảnh đổi từ card có viền sang icon tài liệu (icon + tên + đuôi file), ảnh vẫn hiển thị thumbnail thật
- [x] Menu/popover tự đóng khi click ra ngoài, nhấn Esc, hoặc chuột phải chỗ khác
- [x] Backend không đổi thêm gì mới ngoài `rename` folder đã có — Download/Open/Move/Rename file đều tái dùng endpoint sẵn có, Download dùng thẳng link `/uploads/...` (file vốn đã public)

**Đơn giản hoá có chủ đích so với ảnh tham khảo** (báo lại để biết, không phải thiếu sót):
- **Không có "Cut/Copy" thật** (nhân bản file) — backend không hỗ trợ nhân bản, đã thay bằng **"Move"** (di chuyển, dùng lại tính năng move-to-folder có sẵn), nút ghi rõ "Move" chứ không ghi "Cut/Copy" để không gây hiểu lầm
- **Không có nút chuyển grid/list view** — chỉ có chế độ lưới (list view thật sẽ là 1 bảng riêng, không làm trong lần này)
- **Thư mục không lồng nhau** (flat) — giống thiết kế DB đã chọn từ đầu, không đổi

**Đã test thật qua curl** (không phải chỉ đọc code): upload ảnh → tạo folder → bulk-move ảnh vào folder → xác nhận `data-folder` đúng id sau khi vào folder → xoá cả 3 folder rác tồn dư từ session test trước + folder mới tạo + file test → dọn sạch cả DB lẫn đĩa. 20/20 test suite pass, trang render đủ các thành phần layout mới không lỗi Thymeleaf.

**Lưu ý thành thật còn lại:** phần **tương tác chuột/UX trực tiếp** (bấm đúng vị trí context menu xuất hiện, double-click có mượt không, thanh chọn nhiều có hiện/ẩn đúng lúc không, kéo-thả) mình mới verify qua đọc lại logic + JS parse hợp lệ, **chưa mở trình duyệt thật để thao tác thử** — môi trường này không có trình duyệt tương tác. Bạn thử trực tiếp và báo lại nếu chỗ nào chưa mượt/lệch vị trí nhé.

**Phân loại Files vs Ảnh — đã làm**
- [x] Mở rộng nhận thêm file không phải ảnh: PDF, DOC, DOCX, XLS, XLSX, ZIP, TXT (bên cạnh JPG/PNG/WEBP/GIF/SVG đã có)
- [x] Magic bytes riêng cho từng loại tài liệu (PDF `%PDF`, DOCX/XLSX/ZIP dạng ZIP `PK\x03\x04`, DOC/XLS dạng OLE) — test thật: file giả mạo đổi tên `.pdf` bị chặn đúng lỗi "does not match its declared type"
- [x] Bộ lọc "All files / Images only / Files only" (`media-filter-kind`) + dropdown loại file chia nhóm Images/Files
- [x] File không phải ảnh hiển thị card icon (đuôi file in hoa) thay vì `<img>` vỡ trong grid lẫn panel chi tiết
- [x] Bỏ qua bước sinh thumbnail/đọc kích thước/nén TinyPNG cho file không phải ảnh (các bước này vốn chỉ có ý nghĩa với ảnh)
- [x] Media Picker (chọn cover cho bài viết) chỉ hiển thị ảnh, tự lọc bỏ file tài liệu — test thật xác nhận API trả đúng danh sách

**MVP — đã xong**
- [x] Upload 1 file, validate content-type + size ở server (`MediaServiceImpl`)
- [x] Kiểm tra magic bytes (không chỉ tin đuôi file)
- [x] Lưu file vật lý ngoài classpath (`./uploads`, cấu hình qua `app.upload.dir`)
- [x] Tên file lưu = UUID (chặn path traversal/trùng tên)
- [x] Bảng `media` + metadata đầy đủ (`V7__Media.sql`)
- [x] Serve file public qua `/uploads/**` (`WebConfig` + `SecurityConfig`)
- [x] Danh sách dạng grid (`media.html`)
- [x] Xoá file (DB + đĩa), có dialog xác nhận dùng chung `khDialog`
- [x] Chặn xoá nếu ảnh đang là cover của post nào đó
- [x] Kéo-thả (drag & drop) + click để chọn file (`media.js`)
- [x] Media Picker modal ở Post editor, thay cho việc chỉ nhập tay URL
- [x] Tích hợp nén ảnh qua TinyPNG API, có fallback lưu ảnh gốc nếu lỗi/hết quota (`TinifyClient`)

**MVP — hoàn tất 100%**
- [x] Upload nhiều file cùng lúc (`files[]`, kéo-thả hoặc chọn nhiều)
- [x] Gọi TinyPNG **bất đồng bộ** (`@Async` qua `MediaOptimizationService`, upload trả response ngay, nén chạy nền)
- [x] Sinh thumbnail riêng cho grid (resize cục bộ bằng `java.awt`/`ImageIO`, không tốn quota TinyPNG, bỏ qua nếu ảnh đã nhỏ hơn 320px hoặc là SVG)

**Giai đoạn sau — đã làm**
- [x] Tìm kiếm theo tên file (client-side, tức thời)
- [x] Lọc theo loại file (`media-filter-type`) và theo tình trạng sử dụng (`media-filter-usage`: used/unused)
- [x] Sắp xếp (mới nhất, cũ nhất, lớn nhất, nhỏ nhất, tên A-Z)
- [x] Panel chi tiết file — click ảnh hoặc nút "Details" mở offcanvas: preview, kích thước, dung lượng (kèm % tiết kiệm nếu đã optimize), ngày upload
- [x] Chỉnh sửa alt text (`MediaUpdateRequest`, validate `@NotBlank`/`@Size` cả 2 phía)
- [x] Đổi tên hiển thị (`displayName` → cập nhật `original_filename`, không đổi tên file vật lý)
- [x] Nút copy URL nhanh (Clipboard API, fallback `execCommand`)
- [x] Đánh dấu & lọc file mồ côi — badge "Used"/"Unused" tính từ `PostRepository.findDistinctCoverImages()`, lọc được qua dropdown
- [x] Phân trang → **infinite scroll** thật (12 file/lần, `IntersectionObserver` tự load khi cuộn tới, nút "Load more" làm fallback cho trình duyệt không hỗ trợ) + lazy-load ảnh (`loading="lazy"`)
- [x] Chọn nhiều + xoá hàng loạt (`POST /admin/media/bulk-delete`, best-effort — bỏ qua file đang được dùng, báo lỗi riêng từng file) + Export CSV (tải danh sách đang lọc/hiển thị)
- [x] Lọc theo người upload (dropdown lấy danh sách distinct từ `mediaList`)
- [x] Lọc theo khoảng thời gian upload (input `type="date"` từ/đến)
- [x] Giới hạn rate upload — `UploadRateLimiter` in-memory (sliding window, tối đa 20 file/10 phút mỗi user), test thật: upload liên tục 21 file → file thứ 21 bị chặn đúng thông báo, 20 file trước đó lưu thành công

- [x] Thư mục/album — thư mục phẳng (`MediaFolder`), gán 0-1 thư mục/file, lọc theo thư mục qua chip, "Move to folder" hàng loạt, xoá thư mục tự chuyển file về "Unfiled" (`ON DELETE SET NULL`)

**Giai đoạn sau — chưa làm**
- [ ] Phân quyền upload/xoá theo role — **cần làm module Roles thật trước** (hiện `RoleController`/`roles.html` mới là UI mock, chưa có entity `Role`/quan hệ với `User`, `SecurityConfig` chưa phân biệt quyền theo role); khi có Roles thật sẽ quay lại gắn `hasRole(...)` cho các endpoint `/admin/media/**`
- [ ] Virus/malware scan — cần hạ tầng ClamAV (hoặc dịch vụ tương đương) chạy cạnh ứng dụng để test được, môi trường hiện tại chưa có nên tạm hoãn
- [ ] Kéo-thả sắp xếp thumbnail, gắn tag cho media, CDN, video upload, crop ảnh trong trình duyệt

---

## 1. Nhóm tính năng cốt lõi (MVP)

- **Upload file**: chọn 1 hoặc nhiều file cùng lúc, kéo-thả (drag & drop) vào vùng upload.
- **Lưu trữ vật lý**: lưu file lên đĩa server (thư mục `uploads/` ngoài classpath, không lưu trong `static/` để tránh mất khi rebuild) hoặc object storage (S3-compatible) — cấu hình qua `application.yaml`.
- **Metadata trong DB**: bảng `media` lưu tên file gốc, tên file lưu trữ (unique), đường dẫn/URL, content-type, dung lượng (bytes), kích thước ảnh (width/height nếu là ảnh), người upload, thời gian tạo.
- **Danh sách dạng lưới (grid)**: hiển thị thumbnail, tên file, dung lượng — tái dùng pattern DataTables/phân trang đã có ở Users/Categories nhưng dạng grid thay vì table.
- **Xoá file**: xoá cả bản ghi DB lẫn file vật lý, có dialog xác nhận (tái dùng `khDialog` đã có trong `admin.js`).
- **Giới hạn loại file & dung lượng**: whitelist content-type (jpg/png/webp/svg/gif, có thể thêm pdf), giới hạn kích thước tối đa (vd 5–10MB), validate cả client (trước khi upload) lẫn server (bắt buộc, không tin client).
- **Chọn ảnh cho bài viết**: thay ô "Cover image URL" đang nhập tay ở Post editor bằng một **Media Picker** (modal chọn ảnh từ thư viện hoặc upload nhanh), trả về URL vào field cover.

## 2. Tổ chức & tìm kiếm

- **Tìm kiếm theo tên file**.
- **Lọc theo loại file** (ảnh / tài liệu / video nếu hỗ trợ).
- **Lọc theo người upload** (nếu nhiều tác giả).
- **Lọc theo khoảng thời gian upload**.
- **Sắp xếp**: theo ngày upload, dung lượng, tên (A-Z).
- **Thư mục / album** (tuỳ chọn giai đoạn sau): gom nhóm media theo chuyên mục hoặc theo tháng để dễ quản lý khi số lượng file lớn.

## 3. Xem chi tiết & chỉnh sửa metadata

- **Panel chi tiết file**: click vào 1 item mở side panel/offcanvas hiển thị preview lớn, đầy đủ metadata (kích thước, dung lượng, ngày upload, URL).
- **Alt text / caption**: cho phép nhập/sửa alt text (quan trọng cho SEO & accessibility khi ảnh được chèn vào bài viết).
- **Copy URL**: nút copy nhanh đường dẫn public của file.
- **Đổi tên hiển thị** (không đổi tên file vật lý, tránh vỡ link đã dùng).

## 4. Kiểm soát sử dụng (usage tracking)

- **Theo dõi nơi sử dụng**: biết file nào đang được dùng làm cover cho post nào (join `posts.cover_image` hoặc bảng liên kết `post_media` nếu về sau hỗ trợ nhiều ảnh/bài).
- **Cảnh báo trước khi xoá**: nếu file đang được dùng ở ≥1 bài viết, cảnh báo rõ ràng hoặc chặn xoá cho tới khi gỡ liên kết.
- **Đánh dấu file mồ côi (unused)**: lọc nhanh các file không còn được tham chiếu ở đâu, hỗ trợ dọn dẹp định kỳ.

## 5. Xử lý ảnh (image processing) — nén qua TinyPNG API

**Quyết định:** dùng [TinyPNG API](https://tinypng.com/developers) (tinify.com) để nén ảnh khi upload, thay vì tự xử lý bằng thư viện Java hoặc binary ngoài.

- **Luồng xử lý**: sau khi nhận file upload ở server → gọi TinyPNG API (`POST https://api.tinify.com/shrink`, Basic Auth với API key, body là bytes ảnh gốc) → nhận về URL ảnh đã nén → tải ảnh nén về và lưu (thay cho bản gốc, hoặc lưu song song nếu muốn giữ bản gốc).
- **Hỗ trợ định dạng**: JPEG, PNG, WebP (đúng những định dạng Media library dự định nhận).
- **Resize tiện lợi**: TinyPNG API có endpoint resize kèm theo (`method: fit/cover/scale` + width/height) trong cùng 1 lần gọi — dùng để sinh luôn bản thumbnail cho grid mà không cần thư viện resize riêng.
- **API key**: lưu trong `application.yaml`/biến môi trường (`tinify.api-key`), **không hard-code, không commit vào git**.
- **Giới hạn free tier**: 500 ảnh nén/tháng miễn phí, sau đó tính phí theo số ảnh (~$0.009/ảnh cho size ≤500KB) — cần theo dõi quota; nếu vượt hoặc lỗi mạng, **fallback lưu ảnh gốc chưa nén** thay vì chặn upload (không để phụ thuộc bên thứ 3 làm sập tính năng upload).
- **Bất đồng bộ hoá (khuyến nghị)**: gọi TinyPNG trong luồng nền (`@Async` hoặc queue) sau khi đã lưu ảnh gốc và trả response cho user ngay, tránh user phải chờ round-trip tới TinyPNG mới thấy upload xong.
- **Lưu thêm cột** vào bảng `media`: `optimized` (boolean, đã nén qua TinyPNG hay chưa) và `original_size_bytes` (dung lượng trước khi nén) để hiển thị "tiết kiệm được bao nhiêu %" giống UI của TinyPNG.
- **Kiểm tra & chặn ảnh hỏng / không đúng định dạng khai báo** trước khi gửi lên TinyPNG (kiểm tra magic bytes thay vì chỉ tin đuôi file, tránh security risk khi user đổi tên `.exe` thành `.jpg`, và tránh tốn quota TinyPNG cho file rác).

## 6. Phân trang & hiệu năng

- **Phân trang / infinite scroll** cho grid khi số lượng file lớn (không load toàn bộ 1 lần).
- **Lazy-load ảnh** trong grid.

## 7. Bảo mật & phân quyền

- **Giới hạn ai được upload/xoá**: hiện hệ thống chưa có role/authority thật (mọi user đăng nhập đều là "authenticated" ngang nhau) — khi làm Media cần cân nhắc gắn với module Roles đã có sẵn khung UI.
- **Chặn path traversal**: tên file lưu trên đĩa phải là tên sinh ngẫu nhiên/UUID, không dùng trực tiếp tên file người dùng upload.
- **Giới hạn rate upload** để tránh spam làm đầy ổ đĩa.
- **Virus/malware scan** (tuỳ chọn, giai đoạn sau nếu cần mức an toàn cao hơn).

## 8. Chọn nhiều & thao tác hàng loạt

- **Chọn nhiều file** (checkbox như các module khác: Users/Categories/Hashtags đã có sẵn pattern `.rowcheck`/`.allcheck`/bulk bar).
- **Xoá hàng loạt**.
- **Export danh sách** (CSV) — đã có nút mock `Export CSV` ở nhiều trang khác, đồng bộ hành vi.

## 9. Đề xuất thiết kế kỹ thuật (khớp pattern hiện có trong repo)

- **Entity**: `Media` — `id, originalFilename, storedFilename, url, contentType, sizeBytes, width, height, altText, uploadedBy (User), createdAt`.
- **Migration**: `V7__Media.sql` tạo bảng `media`, FK `uploaded_by -> users.id`.
- **Repository/Service/Controller**: theo đúng pattern 3 lớp đã dùng cho Category/Hashtag/Post (`MediaService`, `MediaServiceImpl`, `MediaRepository`, `MediaController` dưới `/admin/media`).
- **Upload endpoint**: `POST /admin/media` nhận `multipart/form-data`, dùng `MultipartFile`, validate content-type + size ở server trước khi lưu.
- **Static serving**: cấu hình thêm `WebMvcConfigurer.addResourceHandlers` để serve thư mục upload ngoài classpath (khác với `static/` hiện tại vốn nằm trong classpath, sẽ bị đóng gói lại mỗi lần build).
- **Frontend**: dùng lại `datatable.js`/`admin.js` cho phần chọn nhiều + dialog xoá; viết thêm `media.js` riêng cho upload (drag-drop, progress bar) và Media Picker modal dùng chung ở Post editor.

## 10. Có thể làm sau (không cần cho MVP)

- Kéo-thả sắp xếp thứ tự thumbnail.
- Gắn thẻ/hashtag cho media để tìm kiếm theo tag.
- Tích hợp CDN.
- Video upload & transcoding.
- Chỉnh sửa ảnh cơ bản ngay trong trình duyệt (crop, xoay).
