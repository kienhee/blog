# Checklist tiến độ i18n (EN / VI)

> Bảng theo dõi cho `docs/plan-i18n.md`. Tick `[x]` khi **đã làm xong + test của phần đó xanh**.
> Cập nhật lần cuối: 2026-09-18 — Phase 1–7 xong, Phase 8 còn 4 việc cần bấm tay; `mvnw.cmd test` xanh toàn bộ (483 test).

## Tổng quan

| Phase | Nội dung | Số việc | Xong |
| --- | --- | --- | --- |
| 1 | Hạ tầng | 11 | **11** |
| 2 | Layout + fragments + public | 19 | **19** |
| 3 | Admin templates | 23 | **23** |
| 4 | Validation (17 DTO, 99 message) | 17 | **17** |
| 5 | Lỗi nghiệp vụ (120) + flash controller (~90) | 21 | **21** |
| 6 | JavaScript + thư viện | 24 | **23** (+1 chờ langpack TinyMCE) |
| 7 | Email | 10 | **10** |
| 8 | Rà soát cuối | 10 | **6** (+4 cần bấm tay) |

---

## Phase 1 — Hạ tầng

- [x] `src/main/resources/messages.properties` (tiếng Việt, bản fallback)
- [x] `src/main/resources/messages_en.properties` (tiếng Anh)
- [x] `application.yaml`: `spring.messages.{basename,encoding,fallback-to-system-locale,use-code-as-default-message}`
- [x] `application-prod.yaml`: `spring.messages.cache-duration: 1h`
- [x] `config/I18nConfig.java` + hằng số `I18n.SUPPORTED` / `I18n.DEFAULT`
- [x] `CookieLocaleResolver` lớp con clamp về `{vi, en}`, cookie `kh_lang`, `SameSite=Lax`, `secure` ở prod
- [x] `controller/LocaleController` — `POST /lang` (CSRF, chặn open-redirect)
- [x] `SecurityConfig.PUBLIC_MATCHERS` += `"/lang"`
- [x] `GlobalControllerAdvice` += `@ModelAttribute("currentLang")`
- [x] Test `MessageCatalogTests` (parity key, không key rỗng/trùng/TODO)
- [x] Test `LocaleSwitchTests` (`code` lạ, `//evil.com`, cookie giữ qua request)

## Phase 2 — Layout + fragments + public

**Nền chung (làm trước, mọi trang dùng)**
- [x] `layout/public-master.html` (30) — `th:lang`, `og:locale`
- [x] `layout/admin-master.html` (46) — `th:lang`
- [x] `fragments/public/header.html` (59) — + nút đổi ngôn ngữ
- [x] `fragments/public/footer.html` (39)
- [x] `fragments/public/pagination.html` (37)
- [x] `fragments/admin/topbar.html` (19) — + nút đổi ngôn ngữ (chữ còn lại: Phase 3)

**Trang public**
- [x] `public/article.html` (130)
- [x] `public/index.html` (65)
- [x] `public/subscribe.html` (54)
- [x] `public/newsletter-status.html` (50)
- [x] `public/search.html` (38)
- [x] `public/author.html` (33)
- [x] `public/about.html` (26)
- [x] `public/categories.html` (23)
- [x] `public/news.html` (22)
- [x] `public/category.html` (22)
- [x] `error/404.html` (27)

**Java kèm theo**
- [x] `PublicViewHelper.date()` — pattern theo locale (bỏ `Locale.ENGLISH`)
- [x] `PublicViewHelper.ago()` — 8 nhánh chuỗi → `common.ago.*`

**Settings song ngữ (không migration)**
- [x] `admin/setting/settings.html` += `site.title.{vi,en}`, `site.meta_description.{vi,en}`; `PublicSiteAdvice` fallback key trần

## Phase 3 — Admin templates

- [x] `fragments/admin/sidebar.html` (65)
- [x] `fragments/admin/dialog.html` (21)
- [x] `fragments/admin/topbar.html` (19) — phần chữ còn lại
- [x] `admin/authentication/login.html` (49)
- [x] `admin/authentication/register.html` (55)
- [x] `admin/authentication/forgot.html` (48)
- [x] `admin/authentication/reset.html` (65)
- [x] `admin/user/users.html` (221)
- [x] `admin/post/post-new.html` (183) — nhớ `data-tip` của `.help`
- [x] `admin/category/categories.html` (172)
- [x] `admin/hashtag/hashtags.html` (158)
- [x] `admin/analytics/dashboard.html` (135)
- [x] `admin/user/profile.html` (132)
- [x] `admin/comment/comments.html` (124)
- [x] `admin/post/posts.html` (117)
- [x] `admin/trash/trash.html` (107)
- [x] `admin/subscriber/subscribers.html` (105)
- [x] `admin/role/roles.html` (98)
- [x] `admin/media/media.html` (51)
- [x] `admin/setting/settings.html` (68)

**Nhãn enum**
- [x] `PostStatus` → `enum.post.status.*`
- [x] `CommentStatus` → `enum.comment.status.*`
- [x] `UserStatus` + `SubscriberStatus` → `enum.user.status.*` / `enum.subscriber.status.*`

## Phase 4 — Validation (99 `message`, 17 DTO)

- [x] Xác nhận `MediaApiController` inject `Validator` của Spring (không phải `Validation.buildDefaultValidatorFactory()`)
- [x] `PostUpdateRequest` (13)
- [x] `PostCreateRequest` (12)
- [x] `UserCreateRequest` (8)
- [x] `HashtagUpdateRequest` (7)
- [x] `CommentForm` (7)
- [x] `CategoryUpdateRequest` (7)
- [x] `UserUpdateRequest` (6)
- [x] `RegisterRequest` (6)
- [x] `HashtagCreateRequest` (6)
- [x] `CategoryCreateRequest` (6)
- [x] `ResetPasswordRequest` (4)
- [x] `MediaUpdateRequest` (4)
- [x] `ChangePasswordRequest` (4)
- [x] `RoleCreateRequest` (3)
- [x] `ProfileUpdateRequest` (2) + `MediaFolderCreateRequest` (2) + `ForgotPasswordRequest` (2)
- [x] Test `MessageUsageTests` (mọi `{validation.*}` và `#{key}` đều tồn tại)

## Phase 5 — Lỗi nghiệp vụ (120 chỗ) + flash message của controller

> Phát sinh thêm trong lúc làm: ~90 chuỗi flash `successMessage`/`errorMessage` và thông báo
> bulk-delete trong controller cũng là chữ người dùng thấy, nên đã đưa vào Phase 5 luôn
> (`BulkDelete` giờ nhận key danh từ/động từ thay vì chữ tiếng Anh).

**Cơ chế**
- [x] `exception/BusinessException(code, args)`
- [x] Helper resolve ở controller (fallback: không có key thì trả nguyên chuỗi)
- [x] Nhánh JSON 422 của `MediaApiController` resolve theo locale

**Di trú theo module** (đổi `throw` + chỗ bắt ở controller + test của module)
- [x] `MediaServiceImpl` (24)
- [x] `MediaFolderServiceImpl` (12)
- [x] `RoleServiceImpl` (10)
- [x] `PostServiceImpl` (10)
- [x] `CategoryServiceImpl` (9)
- [x] `UserServiceImpl` (8)
- [x] `TrashServiceImpl` (8)
- [x] `CommentServiceImpl` (6) + `CommentService` (1)
- [x] `AuthServiceImpl` (5)
- [x] `NewsletterServiceImpl` (4)
- [x] `MediaStorageLayout` (4)
- [x] `HashtagServiceImpl` (4)
- [x] `SettingServiceImpl` (3)
- [x] `FileHasher` (3)
- [x] `FileSizeValidator` (2) + `MagicByteValidator` (1)
- [x] `QuotaServiceImpl` (2)
- [x] `PasswordResetServiceImpl` (2)
- [x] `UserController` (1) + `ProfileController` (1)

## Phase 6 — JavaScript + thư viện

**Cơ chế**
- [x] `fragments/i18n.html` — `<script type="application/json" id="kh-i18n">`
- [x] `window.khT(key, ...args)` trong `core/admin.js` (nội suy `{0}`)
- [x] Public layout đẩy nhóm `js.public.*`

**File JS** (theo thứ tự nặng → nhẹ)
- [x] `media/media-explorer.js`
- [x] `media/image-editor.js`
- [x] `post/post-editor.js`
- [x] `core/admin.js` (`khToast`, `khDialog` — 6 + 15 lời gọi)
- [x] `public/main.js`
- [x] `core/datatable.js`
- [x] `user/user.js`
- [x] `category/category.js`
- [x] `hashtag/hashtag.js`
- [x] `user/profile.js`
- [x] `comment/comments.js`
- [x] `auth/auth.js`
- [x] `trash/trash.js`
- [x] `subscriber/newsletter.js`
- [x] `setting/settings.js`
- [x] `user/user-status.js`
- [x] `media/media.js`
- [x] `role/role.js`
- [x] `post/post-list.js`

**Thư viện vendored**
- [x] DataTables — object `language` cho mọi `table.dt`
- [x] flatpickr — `lib/flatpickr-vi.js` (viết tay, nhỏ) + `flatpickr.localize()`
- [~] TinyMCE 7 — đã nối `language`/`language_url` theo `window.khLang`; **cần tải langpack `vi.js`** vào `scripts/lib/tinymce/langs/` (xem README ở đó) — không tự viết bản dịch của bên thứ ba
- [x] Lightbox2 — `albumLabel`, **giữ `sanitizeTitle`**

## Phase 7 — Email

- [x] Thêm `Locale` vào chữ ký `MailService` (chụp locale ở thread request, không dựa `LocaleContextHolder` trong `@Async`)
- [x] `MailServiceImpl` — `new Context(locale, …)` + subject qua `MessageSource`
- [x] Job không có request (`ScheduledPostPublisher`, `TrashPurgeJob`, newsletter sau commit) → `I18n.DEFAULT`
- [x] `mail/password-reset.html` (41)
- [x] `mail/newsletter-confirm.html` (34)
- [x] `mail/newsletter-issue.html` (32)
- [x] `mail/account-pending-admin.html` (32)
- [x] `mail/account-approved.html` (28)
- [x] Kiểm tra vẫn **chỉ link tuyệt đối**, không `@{}` trong mail template
- [x] Test `MailLocaleTests` (GreenMail, hai locale ra hai subject/body)

## Phase 8 — Rà soát cuối

- [x] `support/TestLocale` + chuyển 25 file test sang assert model / message code (làm **dần từ Phase 2**, chốt ở đây)
- [x] Test `HardcodedTextTests` bật chế độ chặn
- [x] `mvnw.cmd test` xanh toàn bộ
- [x] Rà mọi trang public ở `vi` rồi `en` — chạy app thật, kiểm `<html lang>`, từ khoá hai ngôn ngữ, không còn `#{key}` sót
- [x] Rà 13 trang admin ở `vi` rồi `en` — đăng nhập thật, tất cả 200, dictionary 200 key mỗi ngôn ngữ
- [~] Kiểm tra tràn layout theo `docs/responsive-checklist.md` — **chưa làm**: cần mắt người/Playwright ở nhiều bề rộng, không tự kết luận được bằng curl
- [~] Media explorer + image editor + TinyMCE ở cả hai ngôn ngữ — trang tải OK và chuỗi đã qua `khT`, nhưng **chưa bấm tay** từng hộp thoại
- [~] Trash / bulk delete / dialog xác nhận ở cả hai ngôn ngữ — có test tự động (`TrashTests`, `BulkDeleteTests` bản EN), **chưa bấm tay** bản VI
- [~] Gửi thử 5 loại email ở cả hai ngôn ngữ — `MailServiceImplTests.Languages` kiểm subject + body vi/en qua GreenMail; **chưa gửi thật** qua Gmail
- [x] Cập nhật `CLAUDE.md` mục i18n + đánh dấu `docs/plan-i18n.md` là đã triển khai
