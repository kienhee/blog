# Kế hoạch dữ liệu demo (~200 bài)

Mục tiêu: site demo cho khách trông như một blog lập trình đang chạy thật.

## Quyết định (mặc định, đổi được)
- Nội dung bài viết: tiếng Việt, viết lại, không sao chép từ site nguồn.
- Ảnh bìa: ảnh thật Unsplash/Pexels, khoảng 30–40 ảnh dùng lại giữa các bài.
- Category: làm lại bộ gọn trong `db/demo/V7`: 8–10 mục cha, khoảng 20 mục con, tất cả `visible = 1`.

## Phạm vi
1. Category: mỗi category con có 6–12 bài, không mục nào trống.
2. Bài viết: khoảng 200 bài `PUBLISHED`, thân HTML 400–900 từ (`h2/h3`, danh sách, blockquote, code, bảng), 2–4 hashtag/bài, ngày đăng rải 6–8 tháng bằng `DATE_SUB(NOW(), ...)`.
3. Phụ: 4–6 tác giả (role `user`) có avatar và tiểu sử; 150–250 bình luận (có PENDING và reply); `post_view_daily` 60 ngày; 5–10 bài DRAFT, 2–3 SCHEDULED; vài subscriber CONFIRMED; vài mục trong Trash; một số `site.*` / `social.*`.

## Cách làm
- Mỗi nhóm category một file trong `db/demo` (`V8` trở đi). Tuyệt đối không thêm INSERT demo vào `db/migration`.
- Chia 4–5 lô (~40 bài/lô), mỗi lô duyệt trước khi gộp.
- Test: đủ ~200 bài PUBLISHED, không category hiện nào trống, slug không trùng, mỗi bài có ít nhất một hashtag.
- Kiểm tra: xóa DB, chạy lại, xem trang public và dashboard trên trình duyệt.

## Tiến độ
- [ ] Chốt category + hashtag
- [ ] Ảnh bìa vào thư viện media
- [ ] Tác giả
- [ ] Lô bài 1–5
- [ ] Bình luận, lượt xem, subscriber, trash
- [ ] Test + chạy thử
