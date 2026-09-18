# Kế hoạch đa ngôn ngữ (EN / VI)

> Trạng thái: **bản kế hoạch, chưa triển khai**. Lập ngày 2026-09-18, nhánh `feature/cms-completion`.

## 1. Quyết định đã chốt

| Vấn đề | Quyết định |
| --- | --- |
| Phạm vi | **Chỉ dịch giao diện (UI)**: template public + admin, email, validation, thông báo lỗi, chữ trong JS. Nội dung bài viết / danh mục vẫn một bản duy nhất như hiện nay. |
| Chọn ngôn ngữ | **Cookie + nút đổi ngôn ngữ**. URL không đổi (`/article/{slug}` giữ nguyên), không thêm tiền tố `/vi/`, `/en/`. |
| Mặc định | **Tiếng Việt**. `messages.properties` = tiếng Việt (bản fallback), `messages_en.properties` = tiếng Anh. |
| Không làm ở giai đoạn này | Bảng `*_translations` cho nội dung, `hreflang`, sitemap/RSS theo locale, `users.locale`. Xem §8. |

Hệ quả cần chấp nhận: Google chỉ index bản tiếng Việt (bản EN nằm sau cookie nên crawler không thấy). Nếu sau này cần SEO hai ngôn ngữ thì phải chuyển sang tiền tố URL — xem §8.1.

## 2. Hiện trạng

- **Không có hạ tầng i18n nào**: không có `messages*.properties`, không có `MessageSource`/`LocaleResolver` cấu hình riêng, `WebConfig` rỗng.
- Toàn bộ chữ đang hardcode tiếng Anh ở 5 nơi:
  1. **41 template Thymeleaf** (~2.900 dòng): `public/` 10, `admin/` 17, `fragments/` 6, `layout/` 2, `mail/` 5, `error/` 1.
  2. **99 `message = "..."`** trong annotation `jakarta.validation` ở `dto/`.
  3. **120 `throw new IllegalArgumentException("...")`** ở tầng service — đây chính là chữ người dùng nhìn thấy qua `BindingResult` / flash message / JSON 422.
  4. **~19 file JS** (`static/scripts/**`, ~3.650 dòng, ngoài `lib/`): `khToast`/`khDialog`, `media-explorer.js`, `image-editor.js`, `trash.js`, `post-editor.js`… (~218 chuỗi ứng viên).
  5. **`PublicViewHelper`**: `DateTimeFormatter.ofPattern("MMM d, yyyy", Locale.ENGLISH)` và `ago()` trả về `"just now"`, `"5 minutes ago"`…
- `MailServiceImpl` render template mail với `new Context(Locale.ENGLISH, …)` và chạy **`@Async`** → mất `LocaleContextHolder` của request, phải truyền `Locale` tường minh.
- Thư viện vendored cần bản ngôn ngữ riêng: DataTables.net, flatpickr, TinyMCE 7, Lightbox2.
- **25 file test** assert chuỗi tiếng Anh trong HTML trả về (`containsString(...)`) → sẽ vỡ nếu mặc định đổi sang tiếng Việt. Xem §6.

## 3. Hạ tầng (Phase 1)

### 3.1 File dịch

```
src/main/resources/messages.properties       # tiếng Việt — bản mặc định & fallback
src/main/resources/messages_en.properties    # tiếng Anh
```

Không tạo `messages_vi.properties` (sẽ trùng lặp với `messages.properties`). Khi locale = `en`, key thiếu tự fallback về bản Việt — có test chặn việc thiếu key (§6.2).

`application.yaml`:

```yaml
spring:
  messages:
    basename: messages
    encoding: UTF-8
    fallback-to-system-locale: false
    use-code-as-default-message: false   # thiếu key => lỗi ngay khi dev, không im lặng
```

`application-prod.yaml` thêm `cache-duration: 1h` (song song với `thymeleaf.cache: true`).

### 3.2 Quy ước đặt key

```
common.*            # Lưu, Huỷ, Xoá, Có, Không, phân trang, trạng thái chung
nav.*               # menu public + sidebar admin
public.<page>.*     # public.home.*, public.article.*, public.search.*, public.subscribe.*
admin.<module>.*    # admin.post.*, admin.media.*, admin.user.*, admin.trash.*, admin.setting.*
auth.*              # login, register, forgot, reset
mail.<template>.*   # mail.password_reset.subject, mail.newsletter_confirm.body…
validation.*        # message code của annotation trong dto/
error.<module>.*    # message code của IllegalArgumentException ở service
js.*                # dictionary đẩy xuống client (§5.3)
enum.*              # PostStatus, CommentStatus, UserStatus, SubscriberStatus
```

Quy tắc: key là danh từ theo module, **không** đặt theo nội dung tiếng Anh (`admin.post.status.draft`, không phải `admin.post.draft_label_text`). Tham số dùng `{0}`, `{1}`.

### 3.3 Resolver + endpoint đổi ngôn ngữ

File mới `config/I18nConfig.java`:

- `LocaleResolver` = lớp con của `CookieLocaleResolver` **ghi đè để chỉ nhận `vi` / `en`** (cookie do người dùng sửa được, phải clamp về danh sách trắng, tránh locale rác).
  - cookie `kh_lang`, `maxAge` 1 năm, `path=/`, `SameSite=Lax`, `secure` bật ở profile prod.
  - `setDefaultLocale(Locale.forLanguageTag("vi"))`, **không** dùng `Accept-Language`.
- Hằng số dùng chung: `I18n.SUPPORTED = List.of("vi","en")`, `I18n.DEFAULT = vi`.
- **Không** dùng `LocaleChangeInterceptor` (sẽ để `?lang=` dính vào mọi URL, canonical và link share). Thay bằng `controller/LocaleController`:
  - `POST /lang` (form-post + CSRF, đúng phong cách app): tham số `code` (`vi|en`, sai thì bỏ qua), `redirect` (bắt buộc bắt đầu bằng `/` và **không** bắt đầu bằng `//` → chặn open-redirect; sai thì về `/`).
  - Thêm `"/lang"` vào `SecurityConfig.PUBLIC_MATCHERS`.
- `layout/public-master.html` + `layout/admin-master.html`: `<html th:lang="${#locale.language}">`, thêm `og:locale`.
- Nút đổi ngôn ngữ: `fragments/public/header.html` (cạnh nút theme) và `fragments/admin/topbar.html` (cạnh menu tài khoản) — một form nhỏ với `redirect` = `${currentUri}` (đã có sẵn từ `GlobalControllerAdvice`), kèm query string nếu có.
- `GlobalControllerAdvice` thêm `@ModelAttribute("currentLang")` để template tô đậm ngôn ngữ đang chọn.

### 3.4 Validation

Spring Boot 3+/4 đã nối `LocalValidatorFactoryBean` với `MessageSource`, nên `message = "{validation.post.title.required}"` tự resolve theo locale của request. Cần kiểm tra riêng **`MediaApiController`** vì nó gọi `jakarta.validation.Validator` trực tiếp — phải chắc chắn bean được inject là `LocalValidatorFactoryBean` của Spring, không phải `Validation.buildDefaultValidatorFactory()`.

### 3.5 Lỗi nghiệp vụ từ service

120 chỗ `IllegalArgumentException("Slug already exists")` hiện đẩy thẳng chữ ra UI. Service không được biết HTTP, và cũng không nên biết locale.

Cách làm: thêm `exception/BusinessException extends RuntimeException` với `String code` + `Object[] args`, và một helper `support/BusinessMessages` (hoặc `@ControllerAdvice` cho nhánh JSON) để controller resolve `code` qua `MessageSource`. Giữ `getMessage()` trả về chính `code` để log vẫn đọc được.

Di trú theo module, **không** đổi một lượt: mỗi module đổi `throw`, đổi chỗ bắt trong controller, cập nhật test của module đó. Trong lúc đang di trú, helper resolve theo quy tắc "không tìm thấy key thì trả nguyên chuỗi" để hai kiểu cùng tồn tại được.

## 4. Template (Phase 2–3)

Thứ tự làm — public trước (ít file, thấy kết quả ngay), rồi admin:

1. `layout/*` + `fragments/*` (14 file): header, footer, sidebar, topbar, dialog, pagination. Làm trước vì mọi trang đều dùng.
2. `public/**` (10 file) + `error/404.html`.
3. `mail/**` (5 file) — xem §5.4.
4. `admin/authentication/**` (4 file).
5. `admin/**` còn lại (13 file), nặng nhất là `post-new.html`, `media.html`, `trash.html`, `settings.html`.

Điểm dễ bỏ sót:
- Không chỉ `th:text`: còn `placeholder`, `title`, `aria-label`, `alt`, `<title>`, `meta[name=description]`, và **`data-tip` của tooltip `.help`** (`&#10;` cho ngắt dòng).
- Nội suy: `#{public.news.count(${total})}`, không nối chuỗi trong template.
- Thymeleaf 3.1 **restricted mode** với `th:utext`/`th:src`/`th:href`: `#{...}` được phép, nhưng vẫn không gọi được `@bean` — giữ nguyên lối `th:with` đang dùng cho `safeHtml`.
- **Bẫy đã ghi trong CLAUDE.md**: `@{...}` không lồng trong `${...}`; `th:data-*` bị bỏ khỏi thẻ khi biểu thức ra `""` (JS đọc được `undefined`).
- Chuỗi enum (`PostStatus`, `CommentStatus`, `UserStatus`, `SubscriberStatus`) hiện in trực tiếp tên enum → map qua key `enum.post.status.<STATUS>`.

## 5. Phần không phải template

### 5.1 `PublicViewHelper`

- `date()`: chọn pattern theo `LocaleContextHolder.getLocale()` (`vi` → `d MMMM, yyyy`; `en` → `MMM d, yyyy`), format với đúng locale đó. Bỏ comment "regardless of the visitor's locale".
- `ago()`: 8 nhánh chuỗi → key `common.ago.*` với tham số; tiếng Việt không có số nhiều nên bản VI đơn giản hơn bản EN (`1 minute ago` / `2 minutes ago` vẫn cần hai key ở bản EN).
- `readMinutes` chỉ trả số → nhãn nằm ở template, giữ nguyên.
- Cần inject `MessageSource` vào helper (`@publicView` là bean nên inject được).

### 5.2 Settings đa ngôn ngữ (không cần migration)

`SettingService` là key/value tự do, mọi field `settings[<key>]` đều lưu được. Vì vậy:

- Thêm `site.title.vi` / `site.title.en`, `site.meta_description.vi` / `.en` (và các key văn bản khác nếu có) vào `admin/setting/settings.html` — **không sửa Java, không thêm Flyway**.
- `PublicSiteAdvice.siteTitle()/siteDescription()`: đọc `site.title.<lang>`, thiếu thì fallback key trần `site.title` (giá trị đang có), thiếu nữa thì default hiện tại. Giữ nguyên khả năng tương thích với dữ liệu cũ.

### 5.3 JavaScript

Không có bundler nên không dùng i18n library. Hai cơ chế, dùng đúng chỗ:

1. **Dictionary** cho chuỗi động (toast, dialog, media explorer, image editor): fragment mới `fragments/i18n.html` render một `<script type="application/json" id="kh-i18n">{…}</script>` gồm nhóm key `js.*` cần cho trang đó; `core/admin.js` đọc một lần và cung cấp `window.khT(key, ...args)` (nội suy `{0}`). Public layout đẩy nhóm `js.public.*`.
2. **`data-*` attribute** cho nhãn dùng một lần đã nằm cạnh phần tử (`data-empty-text`, `data-confirm`).

Thư viện vendored:
- **DataTables** (`core/datatable.js`): truyền object `language` (`emptyTable`, `info`, `search`, `paginate.*`, `lengthMenu`) từ dictionary. Áp cho toàn bộ `table.dt`.
- **flatpickr** (editor bài viết): vendor `l10n/vi.js`, `flatpickr.localize(...)` theo `<html lang>`.
- **TinyMCE 7**: vendor langpack `vi.js` vào `static/scripts/lib/tinymce/langs/`, set `language`/`language_url`. Lưu ý editor **đã có sẵn cơ chế rebuild khi đổi theme** — tái dùng đúng chỗ đó khi đổi ngôn ngữ.
- **Lightbox2**: `albumLabel` ("Ảnh %1 / %2"); **giữ `sanitizeTitle`** đang bật ở `core/datatable.js` và trong explorer.
- `khSlugify` đã transliterate tiếng Việt (NFD + `đ`→`d`) → **không đổi gì**.

### 5.4 Email

`MailService` chạy `@Async` nên `LocaleContextHolder` không còn giá trị ở thread gửi. Sửa:

- Thêm `Locale` (hoặc `String lang`) vào chữ ký các method `MailService`; **chụp locale ở thread request** rồi truyền vào.
- `MailServiceImpl`: `new Context(locale, variables)`, và subject cũng lấy từ `MessageSource` với locale đó.
- Template `mail/**`: dùng `#{mail.*}`; giữ nguyên quy tắc **chỉ link tuyệt đối, không `@{}`**.
- Ngữ cảnh không có request (job `ScheduledPostPublisher`, `TrashPurgeJob`, gửi newsletter sau commit): dùng `I18n.DEFAULT` (vi).
- Newsletter: `Subscriber` chưa có cột locale → **giai đoạn 1 gửi theo ngôn ngữ mặc định của site**. Cột `subscribers.locale` là việc của Phase 2 (§8.2).
- `app.mail.enabled=false` vẫn chỉ log subject + recipient đã mask — không log body.

## 6. Kiểm thử

### 6.1 Sửa test hiện có

25 file test assert chuỗi tiếng Anh trong HTML. Nguyên tắc chuyển:

- Ưu tiên **assert model attribute / redirect / status**, không assert chữ hiển thị (CLAUDE.md đã khuyến nghị lối này cho test public).
- Khi buộc phải assert chữ: gửi kèm cookie `kh_lang=en` (hoặc set locale qua `MockMvc` request) và assert bản EN, để test độc lập với việc đổi bản dịch tiếng Việt.
- Lỗi nghiệp vụ: assert **message code**, không assert câu tiếng Anh.
- Thêm helper `support/TestLocale` (bên cạnh `TestAuth`) cho việc này.

### 6.2 Test mới (kiểu guard, giống `PermissionCatalogTests` / `DemoDataSeparationTests`)

1. `MessageCatalogTests`: `messages.properties` và `messages_en.properties` **cùng tập key**, không key rỗng, không key trùng, không còn placeholder `TODO`.
2. `MessageUsageTests`: mọi `#{key}` trong template và mọi `{validation.*}` trong DTO đều có key tương ứng; (chiều ngược lại — key không dùng ở đâu — chỉ cảnh báo, vì key `js.*`/`error.*` được ghép động).
3. `HardcodedTextTests`: quét template tìm text node chữ cái dài hơn N ký tự nằm ngoài `th:text`/`th:utext`/`<script>`/`<style>` — chặn hồi quy khi thêm trang mới. Có danh sách loại trừ.
4. `LocaleSwitchTests`: `POST /lang` set cookie đúng; `code` lạ bị bỏ qua; `redirect` ngoài site (`//evil.com`, `http://…`) bị chặn về `/`; trang render theo cookie.
5. `MailLocaleTests`: mở rộng `MailServiceImplTests` (GreenMail) — cùng một mail gửi ở hai locale ra hai subject/body khác nhau.

## 7. Lộ trình

| Phase | Nội dung | Deliverable / tiêu chí xong |
| --- | --- | --- |
| 1 | Hạ tầng: `messages*.properties`, `I18nConfig`, `LocaleController`, `PUBLIC_MATCHERS`, nút đổi ngôn ngữ, `<html lang>`, `MessageCatalogTests`, `LocaleSwitchTests` | Đổi được ngôn ngữ, cookie giữ qua các request, chưa dịch nội dung nào |
| 2 | `layout/*` + `fragments/*` + `public/**` + `error/404` + `PublicViewHelper` (date/ago) + `site.title.<lang>` | Toàn bộ site public song ngữ, không còn chữ hardcode |
| 3 | `admin/authentication/**` rồi `admin/**` (13 file) + nhãn enum | Admin song ngữ |
| 4 | Validation: 99 `message` → message code (theo module) | Form báo lỗi theo ngôn ngữ đang chọn |
| 5 | `BusinessException` + di trú 120 `IllegalArgumentException` theo module, kèm test từng module | Lỗi nghiệp vụ (trùng slug, tự xoá mình, folder đang dùng, 422 JSON…) theo ngôn ngữ |
| 6 | JS: `khT` + dictionary, DataTables / flatpickr / TinyMCE / Lightbox locale | Toast, dialog, media explorer, editor song ngữ |
| 7 | Mail: chữ ký `Locale`, template `mail/**`, `MailLocaleTests` | Email theo ngôn ngữ của người thao tác |
| 8 | Rà soát: bật `HardcodedTextTests` ở chế độ chặn, chạy tay hai ngôn ngữ trên mọi trang, kiểm tra tràn layout với chuỗi tiếng Việt dài hơn | Không còn chữ lẫn ngôn ngữ; checklist responsive vẫn đạt |

Phase 1→3 nên làm liên tục (một nhánh). Phase 4–7 tách commit theo module để review được.

## 8. Cố ý chưa làm

1. **SEO hai ngôn ngữ**: cookie nghĩa là crawler chỉ thấy tiếng Việt. Muốn index cả EN thì phải chuyển sang tiền tố `/en/`, `/vi/` — ảnh hưởng mọi route, mọi `@{...}`, `SecurityConfig`, `FeedController` (`sitemap.xml` tách theo locale + `hreflang`, `rss.xml` mỗi locale một feed), và cần redirect canonical. Thiết kế lại khi có nhu cầu thật.
2. **Nội dung đa ngôn ngữ** (`post_translations`, `category_translations`, slug theo locale, editor 2 tab, fallback khi thiếu bản dịch, `subscribers.locale`, `users.locale`): scope riêng, cần Flyway `V7+` và sửa `PublicBlogService`. Không trộn vào giai đoạn này.
3. **Dịch dữ liệu demo** (`db/demo` V7/V8) — giữ như đang có; nhớ quy tắc **không bao giờ thêm `INSERT` demo vào `db/migration`**.

## 9. Rủi ro

- **Trộn ngôn ngữ giữa các phase**: trong lúc làm, một trang có thể nửa Việt nửa Anh. Chấp nhận trên nhánh, không merge vào `main` giữa Phase 2/3 của cùng một trang.
- **Test vỡ hàng loạt ở Phase 2**: xử lý bằng `TestLocale` + chuyển sang assert model **trước khi** đổi chữ trong template.
- **Chuỗi tiếng Việt dài hơn tiếng Anh khoảng 20–30%**: nút và nhãn trong admin dễ tràn; chạy lại `docs/responsive-checklist.md` ở Phase 8.
- **Key thiếu im lặng**: đã chặn bằng `use-code-as-default-message: false` + `MessageCatalogTests`.
- **Rate limit / cache**: cookie ngôn ngữ không ảnh hưởng `LoginThrottleFilter`, `CommentRateLimiter`, `UploadRateLimiter` (đều theo IP/email). Nếu sau này đặt CDN cache trước app thì phải `Vary: Cookie`.

---

Tiến độ triển khai theo dõi ở `docs/i18n-checklist.md`.
