# Media Upload & File Management — Implementation Plan

> **Ghi chú hiện trạng (đọc trước khi làm)**
>
> Plan này thiết kế cho stack **Spring Boot REST API + ReactJS SPA + MySQL**, storage local → swap S3.
> Repo hiện tại (`d:/workspace/production/blog`) đang là **Thymeleaf + jQuery (server-rendered, form POST)**, không phải React.
> Những gì **đã có và tái sử dụng được gần như nguyên vẹn ở tầng service/storage**:
> `MediaServiceImpl` (validate content-type + magic bytes, UUID filename, chống path traversal), `MediaOptimizationService` (nén async), `UploadRateLimiter`, `WebConfig` (serve `/uploads/**`), `MediaFolder` (phẳng), thumbnail generator local.
> Những gì **phải làm lại / làm mới**: REST API trả JSON (thay vì redirect + flash), folder **nested**, soft delete + trash, versioning, ACL, share link, chunked upload, blob dedupe, quota, audit log.
> Phần frontend jQuery hiện tại (`media.js`, explorer UI) **không port sang React được** — coi như spec UX tham khảo, viết lại bằng React.

---

## Mục lục
- [Phần A — Kiến trúc tổng quan](#phần-a--kiến-trúc-tổng-quan)
- [Phần B — Database Design](#phần-b--database-design)
- [Phần C — Chi tiết từng chức năng](#phần-c--chi-tiết-từng-chức-năng)
- [Phần D — Roadmap triển khai](#phần-d--roadmap-triển-khai)
- [Phần E — Rủi ro kỹ thuật](#phần-e--rủi-ro-kỹ-thuật)

---

# Phần A — Kiến trúc tổng quan

## A.1 Sơ đồ layer

```
┌─────────────────────────────────────────────────────────────┐
│ React SPA — upload widget, explorer, picker modal, viewer    │
└───────────────┬─────────────────────────────────────────────┘
                │ REST/JSON + multipart, JWT/session
┌───────────────▼─────────────────────────────────────────────┐
│ Controller layer  (@RestController)                          │
│  MediaFileController · FolderController · UploadController   │
│  ShareController · PublicShareController · SearchController  │
│  — chỉ validate DTO + map response, KHÔNG business logic     │
└───────────────┬─────────────────────────────────────────────┘
┌───────────────▼─────────────────────────────────────────────┐
│ Service layer (business + transaction boundary)              │
│  FileService · FolderService · UploadSessionService          │
│  VersionService · AclService · ShareLinkService              │
│  QuotaService · SearchService · TrashService                 │
└──┬────────────┬─────────────┬──────────────┬────────────────┘
   │            │             │              │
┌──▼─────┐ ┌────▼──────┐ ┌────▼────────┐ ┌───▼──────────────┐
│ JPA    │ │ Storage   │ │ Processing  │ │ Async workers    │
│ Repos  │ │ Adapter   │ │ Pipeline    │ │ (@Async / queue) │
│        │ │ (iface)   │ │ (iface)     │ │ thumbnail, scan, │
│        │ │ Local│S3  │ │ image/video │ │ zip, cleanup     │
└────────┘ └───────────┘ └─────────────┘ └──────────────────┘
```

**Nguyên tắc**: Controller mỏng · Service giữ transaction · Storage/Processing là interface (không cho service biết đang local hay S3) · mọi thao tác nặng/gọi ngoài đẩy xuống worker.

## A.2 Các abstraction chính

| Interface | Method chính | Lý do tách |
|---|---|---|
| `StorageAdapter` | `put(key, stream, meta)`, `get(key)`, `delete(key)`, `exists(key)`, `presignedUrl(key, ttl)` | Swap local ↔ S3/GCS mà service không đổi 1 dòng. `presignedUrl` bắt buộc có trong interface — nếu thiếu, sau này lên S3 vẫn phải stream file qua app server (lãng phí băng thông) |
| `MultipartStorageAdapter` (extends trên) | `initMultipart(key)`, `uploadPart(uploadId, idx, stream)`, `completeMultipart(uploadId, parts)`, `abortMultipart(uploadId)` | Chunked upload: S3 có Multipart API **native**. Nếu không tách, app server phải tự merge chunk → tốn disk + băng thông gấp đôi khi lên cloud |
| `FileValidator` (chain) | `validate(FileUploadContext)` | Chain: size → extension/mime whitelist → **magic bytes** → quota → virus. Thêm rule mới không sửa service |
| `ThumbnailGenerator` | `supports(mime)`, `generate(source, spec)` | Ảnh dùng ImageIO/Thumbnailator, video cần ffmpeg, PDF cần pdfbox → mỗi impl 1 class, chọn theo `supports()` |
| `ImageProcessor` | `crop`, `resize`, `compress`, `convert`, `watermark` | Tách khỏi FileService để test độc lập, và để dễ thay engine (ImageIO → libvips khi cần perf) |
| `PermissionEvaluator` | `hasPermission(user, resourceType, resourceId, perm)` | Dùng chung cho cả `@PreAuthorize` lẫn filter list. Nếu viết rải rác trong service sẽ sót chỗ |
| `QuotaService` | `reserve(userId, bytes)`, `release(userId, bytes)` | Atomic counter, tránh race 2 request upload cùng lúc vượt quota |
| `AuditLogger` | `log(actor, action, resource, meta)` | Fire-and-forget qua `ApplicationEvent` → không block request chính |
| `VirusScanner` | `scan(stream)` → CLEAN/INFECTED/ERROR | No-op impl cho dev (không cần ClamAV chạy local), real impl cho prod |

## A.3 Luồng dữ liệu một request upload (single, non-chunked)

```
1. POST /api/media/files (multipart) 
2. Controller → validate DTO (folderId, file present)
3. AclService.check(user, FOLDER, folderId, UPLOAD)          ← 403 nếu fail
4. QuotaService.reserve(userId, fileSize)                     ← 507 nếu vượt (atomic UPDATE)
5. FileValidator.chain: size → mime whitelist → magic bytes   ← 415/413 nếu fail
6. Stream file → tính SHA-256 **trong lúc stream** (1 lần đọc)
7. Dedupe: tìm storage_blob theo sha256
   ├─ Có → ref_count++ , KHÔNG ghi file mới (tiết kiệm disk)
   └─ Không → StorageAdapter.put(key, stream) → ghi file vật lý
8. [TRANSACTION BẮT ĐẦU]
   - INSERT storage_blob (nếu mới)
   - INSERT media_file (status = PENDING_SCAN nếu bật virus scan, else ACTIVE)
   - INSERT file_version (version_no = 1)
   - UPDATE media_file.current_version_id
   [TRANSACTION COMMIT]                                       ← rollback → xoá file vừa ghi (compensating)
9. Publish event: FileUploadedEvent
10. Response 201 + FileDto (kèm status)
11. [ASYNC] worker nhận event:
    - virus scan → cập nhật status ACTIVE / QUARANTINED
    - thumbnail generation → cập nhật thumbnail_key
    - EXIF extraction → ghi file_metadata
    - audit log
```

**Điểm mấu chốt**: bước 6–7 làm **ngoài** transaction (I/O chậm), transaction ở bước 8 chỉ chứa vài INSERT → ngắn, ít lock.

## A.4 Sync vs Async — quyết định rõ ràng

| Thao tác | Sync/Async | Lý do |
|---|---|---|
| Validate (size, mime, magic bytes) | **Sync** | Phải chặn trước khi tốn disk |
| Ghi file vật lý | **Sync** | User cần biết upload thành công hay không |
| Tính hash SHA-256 | **Sync** (streaming) | Đọc 1 lần cùng lúc ghi, không tốn thêm I/O |
| Ghi metadata DB | **Sync** | Nguồn sự thật, phải có ngay để hiện trong list |
| **Thumbnail (ảnh)** | **Async** | ~50–300ms/ảnh, upload 20 ảnh = 6s chờ vô nghĩa |
| **Thumbnail (video)** | **Async bắt buộc** | ffmpeg seek + encode, có thể vài giây/file |
| **Virus scan** | **Async** | Gọi daemon ngoài, timeout không lường trước. File ở trạng thái `PENDING_SCAN`, chặn download/share tới khi CLEAN |
| **Nén ảnh / convert format** | **Async** | Gọi API ngoài (TinyPNG) hoặc CPU nặng |
| **EXIF extraction** | **Async** (gộp chung pipeline) | Nhẹ nhưng không cần thiết cho response |
| **ZIP nhiều file** | **Async nếu tổng > ~50MB** | Nhỏ thì stream trực tiếp; lớn thì tạo job → trả `jobId` → FE poll → tải link |
| **Hard delete file vật lý** | **Async** | Chạy theo lịch sau retention, tránh block user |
| **Dọn orphan file / chunk hết hạn** | **Async (scheduled)** | Janitor job hằng đêm |
| **Audit log** | **Async** | Không được làm chậm/hỏng request chính vì log |
| **Move folder (update path subtree)** | **Sync nếu < ~10k node, async nếu lớn** | Xem C2.3 |

## A.5 Transaction boundary — DB vs File System

**Sự thật nền tảng**: DB transaction và file system **không thể atomic với nhau** (không có XA thực tế với disk/S3). Vì vậy phải chọn thứ tự hỏng "ít đau" nhất.

| Thao tác | Thứ tự | Hậu quả khi hỏng giữa chừng | Cách dọn |
|---|---|---|---|
| **Upload** | Ghi file **trước** → commit DB **sau** | Orphan file trên disk (DB không biết) | Janitor job quét file không có blob record, xoá sau 24h |
| **Delete** | Soft delete DB **trước** → xoá file vật lý **sau** (async, sau retention) | File vẫn nằm trên disk dù DB đã xoá | Janitor job |
| **Replace/version** | Ghi blob mới → commit version → **không đụng** blob cũ | Không mất dữ liệu | — |
| **Move/rename** | Chỉ đổi DB, **không đụng** file vật lý | — | Thiết kế: `storage_key` là UUID, **độc lập hoàn toàn** với tên hiển thị và folder. Rename/move = 0 thao tác I/O |

**Quy tắc bất di bất dịch**:
- ❌ Không bao giờ commit DB trước rồi mới ghi file (user thấy record nhưng mở ra lỗi 404 — tệ hơn nhiều so với orphan file).
- ❌ Không bao giờ gọi S3 / virus scanner / TinyPNG **bên trong** `@Transactional` (giữ connection DB trong lúc chờ network = cạn pool).
- ✅ `storage_key` = UUID, không chứa tên file/folder → rename và move không bao giờ cần chạm file system.

---

# Phần B — Database Design

## B.1 ERD (tóm tắt quan hệ)

```
users ──1:N──> media_file ──N:1──> folder ──self FK──> folder (parent)
                   │  │
                   │  └──1:N──> file_version ──N:1──> storage_blob (ref_count)
                   │
                   ├──1:N──> file_metadata (EXIF, key-value)
                   ├──M:N──> tag (qua file_tag)
                   └──1:N──> share_link

acl_entry ──> (resource_type, resource_id) đa hình: FILE | FOLDER
storage_quota ──1:1──> users
audit_log ──> append-only
upload_session ──1:N──> upload_chunk
```

## B.2 Chi tiết bảng

### `storage_blob` — nội dung vật lý (content-addressable)
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | BIGINT PK | |
| sha256 | CHAR(64) | **UNIQUE** — nền tảng của dedupe |
| size_bytes | BIGINT | |
| storage_key | VARCHAR(255) | UUID path, vd `2026/09/uuid.bin` |
| storage_provider | VARCHAR(20) | `LOCAL` / `S3` — cho phép migrate dần từng blob |
| ref_count | INT | Số file_version đang trỏ tới. =0 → janitor xoá |
| created_at | TIMESTAMP | |

**Index**: `UNIQUE(sha256)`, `INDEX(ref_count)` (janitor quét `ref_count = 0`).

> **Lý do tách blob khỏi file** — quyết định thiết kế quan trọng nhất ở đây:
> 1. **Dedupe**: 50 user upload cùng 1 logo → 1 blob, tiết kiệm 98% disk.
> 2. **Versioning gần như miễn phí**: mỗi version chỉ là 1 row trỏ blob khác.
> 3. **Rename/move = 0 I/O**.
> 4. **Migrate storage dần dần**: đổi `storage_provider` từng blob, không cần downtime.
> Retrofit việc này **sau khi đã có dữ liệu thật là cực kỳ đau** (phải rehash + move toàn bộ file) → làm ngay từ đầu.

### `folder`
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | BIGINT PK | |
| name | VARCHAR(255) | |
| parent_id | BIGINT FK → folder(id) | NULL = root |
| path | VARCHAR(1000) | **Materialized path**, vd `/1/17/42/` |
| depth | INT | Chặn nesting quá sâu (giới hạn ~20) |
| owner_id | BIGINT FK → users | |
| status | VARCHAR(16) | `ACTIVE` / `TRASHED` |
| deleted_at | TIMESTAMP NULL | |
| created_at / updated_at | TIMESTAMP | |

**Index**: `INDEX(parent_id)`, `INDEX(path)` (prefix search), `UNIQUE(parent_id, name, deleted_at)` — chặn trùng tên trong cùng thư mục.

### `media_file`
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | BIGINT PK | |
| name | VARCHAR(255) | Tên hiển thị, user đổi thoải mái |
| folder_id | BIGINT FK NULL | NULL = root |
| owner_id | BIGINT FK → users | |
| current_version_id | BIGINT FK → file_version | |
| mime_type | VARCHAR(100) | Denormalize từ version để list nhanh |
| size_bytes | BIGINT | Denormalize |
| thumbnail_key | VARCHAR(255) NULL | NULL = chưa generate xong / không áp dụng |
| status | VARCHAR(20) | `PENDING_SCAN` / `ACTIVE` / `QUARANTINED` / `TRASHED` |
| deleted_at | TIMESTAMP NULL | |
| created_at / updated_at | TIMESTAMP | |

**Index**: `INDEX(folder_id, status)` (query list chủ đạo), `INDEX(owner_id)`, `INDEX(status, deleted_at)` (janitor trash), `FULLTEXT(name)` (search).

### `file_version`
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | BIGINT PK | |
| file_id | BIGINT FK → media_file | |
| version_no | INT | Tăng dần từ 1 |
| blob_id | BIGINT FK → storage_blob | |
| size_bytes / mime_type | | Snapshot tại thời điểm đó |
| created_by | BIGINT FK → users | |
| note | VARCHAR(255) NULL | vd "rollback về v2" |
| created_at | TIMESTAMP | |

**Index**: `UNIQUE(file_id, version_no)`, `INDEX(blob_id)`.

### `acl_entry`
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | BIGINT PK | |
| resource_type | VARCHAR(10) | `FILE` / `FOLDER` |
| resource_id | BIGINT | Không FK được (đa hình) — đánh đổi có chủ ý |
| subject_type | VARCHAR(10) | `USER` / `ROLE` / `PUBLIC` |
| subject_id | BIGINT NULL | NULL khi `PUBLIC` |
| permission_mask | INT | **Bitmask**: VIEW=1, DOWNLOAD=2, UPLOAD=4, EDIT=8, DELETE=16, SHARE=32, MANAGE_ACL=64 |
| created_at | TIMESTAMP | |

**Index**: `UNIQUE(resource_type, resource_id, subject_type, subject_id)`, `INDEX(subject_type, subject_id)`.

> Dùng **bitmask** thay vì mỗi quyền 1 row: 1 row/subject thay vì 7 rows → check quyền là 1 phép AND, không phải join nhiều dòng.

### `share_link`
| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | BIGINT PK | |
| token | VARCHAR(64) | **UNIQUE**, random 32 bytes base64url (không dùng UUID tuần tự) |
| resource_type / resource_id | | FILE / FOLDER |
| created_by | BIGINT FK | |
| password_hash | VARCHAR(255) NULL | BCrypt, NULL = không đặt mật khẩu |
| expires_at | TIMESTAMP NULL | NULL = vĩnh viễn |
| max_downloads | INT NULL | NULL = không giới hạn |
| download_count | INT DEFAULT 0 | |
| allow_upload | BOOLEAN | Share folder cho người ngoài upload vào |
| revoked_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |

**Index**: `UNIQUE(token)`, `INDEX(resource_type, resource_id)`, `INDEX(expires_at)`.

### `upload_session` / `upload_chunk` (chunked upload)
| upload_session | Kiểu | | upload_chunk | Kiểu |
|---|---|---|---|---|
| id | BIGINT PK | | upload_session_id | BIGINT FK |
| upload_token | VARCHAR(64) UNIQUE | | chunk_index | INT |
| owner_id / folder_id | BIGINT | | etag | VARCHAR(100) |
| file_name | VARCHAR(255) | | size_bytes | INT |
| total_size / chunk_size | BIGINT / INT | | received_at | TIMESTAMP |
| total_chunks | INT | | **PK**(session_id, chunk_index) | |
| storage_upload_id | VARCHAR(255) NULL | ← S3 multipart uploadId | | |
| status | VARCHAR(20) | `IN_PROGRESS`/`COMPLETED`/`ABORTED` | | |
| expires_at | TIMESTAMP | Janitor dọn session quá hạn | | |

### Còn lại (gọn)
| Bảng | Cột chính | Index |
|---|---|---|
| `tag` | id, name (UNIQUE), color, owner_id | `UNIQUE(name, owner_id)` |
| `file_tag` | file_id, tag_id | PK(file_id, tag_id), `INDEX(tag_id)` |
| `file_metadata` | file_id, meta_key, meta_value | PK(file_id, meta_key) — EXIF dạng key-value, tránh cột chết |
| `storage_quota` | user_id PK, quota_bytes, used_bytes, updated_at | — |
| `audit_log` | id, actor_id, action, resource_type, resource_id, metadata JSON, ip, user_agent, created_at | `INDEX(resource_type, resource_id)`, `INDEX(actor_id, created_at)`, partition theo tháng nếu > 10M rows |

## B.3 Folder tree — chọn chiến lược

| Phương án | Query subtree | Breadcrumb | Move folder | Nhược điểm |
|---|---|---|---|---|
| Adjacency list (chỉ `parent_id`) | Recursive CTE (MySQL 8+) | Recursive query | 1 UPDATE (rẻ nhất) | Mọi lần lấy breadcrumb/subtree đều đệ quy → chậm khi tree sâu |
| **Materialized path** (`path` + `parent_id`) | `WHERE path LIKE '/1/17/%'` — dùng index | Parse chuỗi, **0 query** | UPDATE prefix cho cả subtree | Move folder lớn = update nhiều row; path bị giới hạn độ dài |
| Closure table | JOIN bảng phụ, nhanh nhất | JOIN | Xoá + insert lại toàn bộ cặp ancestor-descendant | Thêm 1 bảng, write amplification lớn (N×M rows) |

**✅ Chọn: Materialized path + giữ `parent_id`** (hybrid)

Lý do:
1. Pattern truy cập thực tế của media library là **đọc nhiều, move ít** → tối ưu cho đọc.
2. Breadcrumb (`Home / Ảnh / 2026`) là thứ hiện **trên mọi màn hình** → có sẵn trong `path`, không tốn query.
3. **ACL kế thừa** (quyền folder cha áp xuống con) cần truy vấn ancestor liên tục → `path` giải quyết bằng 1 câu `IN (parsed_ids)`, closure table thì phải JOIN.
4. Giữ `parent_id` song song để MySQL đảm bảo **FK integrity** (materialized path thuần là chuỗi, DB không validate được).

Move folder (pseudo-code):
```sql
-- Cấm move vào chính subtree của nó (tạo vòng lặp)
IF new_parent.path LIKE CONCAT(moved.path, '%') THEN ABORT;

UPDATE folder
SET path = CONCAT(:newParentPath, SUBSTRING(path, LENGTH(:oldParentPath) + 1)),
    depth = depth + :depthDelta
WHERE path LIKE CONCAT(:oldPath, '%');   -- gồm cả chính nó

UPDATE folder SET parent_id = :newParentId WHERE id = :movedId;
```

---

# Phần C — Chi tiết từng chức năng

## C1. Upload

### C1.1 Single / multiple upload (+ drag & drop + paste clipboard)

> Gộp 3 chức năng: **cùng một endpoint**, chỉ khác cách FE lấy `File` object (input / `dataTransfer.files` / `clipboardData.items`). Không có khác biệt ở backend.

**Mục đích**: đưa file từ máy user lên hệ thống với ít thao tác nhất.

**Logic**:
1. FE gửi **từng file một request riêng** (không gộp N file vào 1 request) → có progress + retry độc lập từng file, 1 file lỗi không kéo cả lô hỏng.
2. BE: check ACL folder đích → `QuotaService.reserve()` → validator chain → stream + hash.
3. Dedupe theo hash → tái dùng blob hoặc ghi mới.
4. Transaction ngắn: insert blob (nếu cần) + media_file + file_version.
5. Publish `FileUploadedEvent` → async pipeline (scan, thumbnail, EXIF).

**API**:
```
POST /api/media/files
Content-Type: multipart/form-data
  file: <binary>, folderId?: number, replaceFileId?: number
→ 201 { id, name, mimeType, size, status, thumbnailUrl, folderId, createdAt }
→ 413 file quá lớn · 415 sai định dạng · 507 vượt quota
```

**Data model**: `storage_blob`, `media_file`, `file_version`, `storage_quota.used_bytes`.

**Edge cases**:
| Tình huống | Xử lý |
|---|---|
| Trùng tên trong folder | Tự động thêm hậu tố `(1)`, `(2)` — **không** ghi đè im lặng |
| Client ngắt giữa chừng | Servlet container ném `IOException` → xoá file tạm + `QuotaService.release()` |
| File 0 byte | Reject 400 |
| Đổi đuôi file để né whitelist (`.exe` → `.jpg`) | **Magic bytes check** — đã có sẵn trong repo hiện tại, giữ nguyên |
| Zip bomb | Không unzip phía server. Nếu có tính năng extract → giới hạn tỉ lệ nén và tổng dung lượng giải nén |
| 2 request cùng hash chạy song song | `UNIQUE(sha256)` → 1 thằng thắng, thằng thua bắt `DataIntegrityViolationException` rồi SELECT lại blob |

**Trade-off**:
- *Nhiều request nhỏ vs 1 request lớn*: chọn nhiều request → progress chính xác từng file, retry rẻ. Đánh đổi: nhiều HTTP overhead (chấp nhận được).
- *Reserve quota trước hay sau*: reserve **trước** (dựa `Content-Length`), release nếu fail → tránh vượt quota khi upload song song. Chấp nhận sai số nhỏ khi client khai man `Content-Length` (đã chặn bởi giới hạn size của container).

### C1.2 Upload progress tracking

**Mục đích**: user thấy % của từng file, biết còn bao lâu.

**Logic**: **Không cần backend làm gì.** Dùng `XMLHttpRequest.upload.onprogress` (hoặc `axios onUploadProgress`) ở FE — trình duyệt tự báo số byte đã gửi.
- Chỉ khi cần **progress phía server** (vd đang xử lý sau khi nhận xong: convert video) thì mới cần endpoint riêng.

**API** (chỉ cho xử lý sau upload):
```
GET /api/media/files/{id}/processing-status
→ 200 { status: PENDING_SCAN|PROCESSING|ACTIVE|QUARANTINED|FAILED, progress?: 0-100 }
```

**Edge cases**: `fetch()` **không hỗ trợ** upload progress → bắt buộc dùng XHR/axios. Với proxy có buffer (nginx `proxy_request_buffering on`), progress nhảy 0→100 đột ngột → tắt buffering cho route upload.

**Trade-off**: WebSocket/SSE cho trạng thái xử lý là *nice-to-have*; polling 2s đủ dùng, đơn giản hơn nhiều → recommend polling ở phase đầu.

### C1.3 Chunked upload (resumable)

**Mục đích**: upload file lớn (video vài GB) không bị fail toàn bộ khi rớt mạng; vượt giới hạn request size của proxy.

**Logic**:
```
1. INIT   → BE tạo upload_session (+ gọi StorageAdapter.initMultipart nếu S3)
           trả về uploadToken + chunkSize khuyến nghị (5–10MB)
2. UPLOAD → FE cắt file, PUT từng chunk (có thể song song 3–4 chunk)
           BE ghi chunk (local: file rời `.part`; S3: uploadPart → lưu etag)
           INSERT upload_chunk (session_id, index, etag)
3. STATUS → FE mất mạng/refresh: GET danh sách chunk đã nhận → chỉ gửi phần thiếu
4. COMPLETE → BE kiểm đủ chunk → merge → validate magic bytes + hash toàn file
              → tạo blob + media_file + version (cùng flow C1.1 từ bước 7)
              → xoá chunk tạm, đóng session
5. ABORT  → xoá chunk + abortMultipart
```
Merge phía local (pseudo-code):
```java
try (OutputStream out = storage.openWrite(finalKey)) {
  for (int i = 0; i < totalChunks; i++)           // TUẦN TỰ theo index
    Files.copy(chunkPath(session, i), out);        // stream, không load vào RAM
}
```

**API**:
```
POST   /api/media/uploads                 { fileName, totalSize, mimeType, folderId }
                                          → 201 { uploadToken, chunkSize, totalChunks }
PUT    /api/media/uploads/{token}/chunks/{index}   (binary body) → 204
GET    /api/media/uploads/{token}         → 200 { receivedChunks: [0,1,2,5], status }
POST   /api/media/uploads/{token}/complete → 201 FileDto
DELETE /api/media/uploads/{token}         → 204
```

**Data model**: `upload_session`, `upload_chunk`.

**Edge cases**:
| Tình huống | Xử lý |
|---|---|
| Chunk gửi trùng | Idempotent: ghi đè chunk cùng index, không tăng đếm 2 lần |
| Chunk thiếu khi COMPLETE | 409 + trả về list index còn thiếu |
| Session bỏ dở | `expires_at` (24h) + janitor xoá chunk mồ côi |
| Quota vượt giữa chừng | Reserve **toàn bộ `totalSize` ngay ở bước INIT**, không reserve từng chunk |
| Tổng chunk ≠ totalSize khai báo | Reject ở COMPLETE, abort session |
| File thật khác mime khai báo | Chỉ validate magic bytes được ở **COMPLETE** (chunk 1 có thể đủ header — có thể check sớm ở chunk 0 để fail nhanh) |

**Trade-off**:
- *Merge ở app server vs S3 multipart native*: với S3 **bắt buộc** dùng native (không tải chunk về merge rồi upload lại — tốn gấp đôi băng thông) → đây chính là lý do tách `MultipartStorageAdapter` ở A.2.
- *Ngưỡng bật chunked*: < 10MB dùng upload thường (đơn giản hơn), ≥ 10MB mới chunked. FE tự quyết.
- Chunk song song nhanh hơn nhưng **local storage merge cần tuần tự** → chỉ song song lúc gửi, merge vẫn theo index.

### C1.4 Validate file type & size
**Mục đích**: chặn file độc hại và file quá khổ trước khi tốn tài nguyên.
**Logic**: chain 4 tầng — (1) `Content-Length` vs limit; (2) extension whitelist; (3) MIME whitelist; (4) **magic bytes** (sự thật duy nhất đáng tin). Cấu hình limit theo loại: ảnh 10MB, video 500MB, document 50MB.
**API**: không có endpoint riêng — áp dụng trong mọi luồng upload. Có thể expose `GET /api/media/upload-config` để FE biết limit mà validate sớm (UX), **nhưng BE vẫn phải validate lại**.
**Edge cases**: SVG chứa `<script>` → sanitize hoặc serve với `Content-Disposition: attachment` + CSP; file không có magic bytes (`.txt`, `.csv`) → chỉ tin extension + giới hạn size.
**Trade-off**: whitelist (an toàn, phải bảo trì) vs blacklist (tiện, luôn sót) → **whitelist**, không bàn cãi.

## C2. Tổ chức (Organize)

### C2.1 Folder structure (nested)
**Mục đích**: tổ chức file theo cây như máy tính.
**Logic**: tạo folder → check ACL parent → check trùng tên trong parent → tính `path = parent.path + newId + '/'` (cần id → insert trước, update path sau, trong cùng transaction) → check `depth <= 20`.
**API**:
```
POST   /api/media/folders          { name, parentId? } → 201 FolderDto
GET    /api/media/folders/tree     → 200 [{ id, name, parentId, depth, hasChildren }]
GET    /api/media/folders/{id}/children → 200 { folders: [], files: [] }
```
**Data model**: `folder`.
**Edge cases**: tên rỗng/ký tự đặc biệt (`/`, `\0`) → reject; trùng tên → reject 409 (folder khác file, không auto-rename vì user chủ động đặt tên); depth quá sâu → 400.
**Trade-off**: load cả cây 1 lần (đơn giản, tốt khi < ~1000 folder) vs lazy-load từng cấp (phức tạp hơn, cần khi nhiều). Recommend: **load cả cây**, tối ưu sau khi có số liệu thật.

### C2.2 Rename file & folder
**Mục đích**: sửa tên hiển thị.
**Logic**: check ACL EDIT → validate tên → check trùng trong cùng parent → UPDATE `name`. **Không chạm file system** (nhờ `storage_key` là UUID).
**API**: `PATCH /api/media/files/{id}` `{ name }` · `PATCH /api/media/folders/{id}` `{ name }` → 200.
**Data model**: `media_file.name` / `folder.name` (path **không đổi** vì path dùng id, không dùng tên).
**Edge cases**: rename thành tên đã tồn tại → 409; rename file đang share → link vẫn sống (token trỏ id, không trỏ tên) ✅.
**Trade-off**: path dùng **id** thay vì tên → rename folder không phải update subtree. Đánh đổi: path không đọc được bằng mắt (chấp nhận, vì breadcrumb tra tên qua 1 query `WHERE id IN (...)`).

### C2.3 Move / Copy
**Mục đích**: sắp xếp lại vị trí file/folder.
**Logic**:
- **Move file**: check ACL nguồn (EDIT) + đích (UPLOAD) → UPDATE `folder_id`. 1 câu lệnh.
- **Move folder**: chống vòng lặp (`new_parent.path LIKE moved.path%` → reject) → UPDATE prefix `path` cho cả subtree (xem B.3) → UPDATE `parent_id`.
- **Copy file**: tạo `media_file` mới + `file_version` mới **trỏ cùng blob**, `ref_count++`. **Không copy byte nào** — copy 1GB tức thì.
- **Copy folder**: đệ quy tạo folder mới + copy từng file (chỉ metadata) → async nếu subtree lớn.
**API**:
```
POST /api/media/files/move   { fileIds: [], targetFolderId }  → 200 { moved, failed: [{id, reason}] }
POST /api/media/files/copy   { fileIds: [], targetFolderId }  → 201
POST /api/media/folders/{id}/move { targetParentId }          → 200
```
**Data model**: `media_file.folder_id`, `folder.path/parent_id/depth`, `storage_blob.ref_count`.
**Edge cases**: move folder vào chính nó/con nó → 400; move hàng loạt mà một số file thiếu quyền → **partial success**, trả rõ cái nào fail (đừng rollback cả lô); quota khi copy → copy trỏ chung blob nên **không tính thêm quota** (hoặc tính theo logical size — cần quyết định chính sách, xem E).
**Trade-off**: move folder subtree lớn (>10k) sẽ khoá bảng lâu → nếu gặp, chuyển sang async job + đánh dấu folder `MOVING`. Ngưỡng này hiếm gặp, đừng tối ưu sớm.

### C2.4 Delete: soft delete (trash) + hard delete + restore
**Mục đích**: xoá nhầm còn cứu được; giải phóng disk thật sau retention.
**Logic**:
- **Soft delete**: check ACL DELETE → UPDATE `status = TRASHED`, `deleted_at = now()`. Với folder: cascade toàn subtree (`WHERE path LIKE '/1/17/%'`). File vật lý **không đụng**.
- **Restore**: UPDATE về `ACTIVE`. Nếu folder cha đã bị xoá vĩnh viễn → restore về root và báo cho user.
- **Hard delete**: xoá row → `ref_count--` trên blob → nếu `ref_count = 0` → đánh dấu blob để janitor xoá file vật lý (async).
- **Auto purge**: scheduled job xoá cứng những gì `TRASHED` quá retention (vd 30 ngày).
**API**:
```
DELETE /api/media/files/{id}                 → 204  (soft)
DELETE /api/media/files/{id}?permanent=true  → 204  (hard, cần quyền cao hơn)
POST   /api/media/trash/restore { fileIds: [], folderIds: [] } → 200
GET    /api/media/trash                      → 200 (list)
DELETE /api/media/trash                      → 204 (empty trash)
```
**Data model**: `media_file.status/deleted_at`, `folder.status/deleted_at`, `storage_blob.ref_count`.
**Edge cases**:
| Tình huống | Xử lý |
|---|---|
| Xoá file đang là avatar/cover bài viết | **Chặn** + báo rõ đang dùng ở đâu (repo hiện tại đã làm đúng hướng này) |
| Restore khi folder cha đã mất | Restore về root, thông báo |
| File trong trash có chiếm quota không? | **Có** — nếu không tính, user xoá tạm để né quota. Phải nói rõ trên UI |
| Hard delete trong lúc có người đang download | Xoá file vật lý trễ (janitor) → request đang chạy vẫn xong |
| Blob còn version cũ trỏ tới | `ref_count > 0` → không xoá file vật lý |
**Trade-off**: đây là lý do `ref_count` phải chính xác tuyệt đối. Recommend: **không xoá blob ngay khi ref_count=0**, chờ grace period 24h (phòng bug logic làm mất dữ liệu không cứu được).

## C3. Xem & Preview

### C3.1 Thumbnail generation (image, video)
**Mục đích**: lưới file tải nhanh, không kéo ảnh gốc vài MB.
**Logic** (async worker): nhận `FileUploadedEvent` → chọn generator theo mime (`supports()`) → ảnh: decode + resize (giữ tỉ lệ, cạnh dài 320px); video: `ffmpeg -ss 00:00:01 -vframes 1` → lưu blob thumbnail riêng (prefix key `thumb/`) → UPDATE `media_file.thumbnail_key` → FE poll/refresh thấy ảnh.
**API**: `GET /api/media/files/{id}/thumbnail` → 302 tới presigned URL hoặc stream trực tiếp.
**Data model**: `media_file.thumbnail_key`.
**Edge cases**: ảnh hỏng/không decode được → giữ `thumbnail_key = NULL`, FE hiện icon theo loại file; SVG → dùng luôn file gốc (vector, không cần resize); ảnh CMYK/HEIC → ImageIO không đọc được → cần thư viện bổ sung (TwelveMonkeys / libvips); ảnh khổng lồ (20000×20000) → decode nổ RAM → **giới hạn pixel** trước khi decode (`ImageReader.getWidth()` đọc header trước).
**Trade-off**: sinh sẵn (eager, tốn disk, xem nhanh) vs sinh khi cần (lazy, chậm lần đầu). Recommend: **eager cho ảnh** (rẻ), **eager 1 size cho video** (đắt nhưng quan trọng), thêm size khác thì lazy.

### C3.2 Preview viewer (image, video, PDF, document)
**Mục đích**: xem nội dung không cần tải về.
**Logic**: BE chỉ cần trả stream đúng `Content-Type` + hỗ trợ **HTTP Range** (bắt buộc cho video tua). Render là việc của FE:
| Loại | Cách render |
|---|---|
| Image | `<img>` |
| Video | `<video>` + Range request |
| PDF | **pdf.js ở FE** — không cần backend render |
| DOCX/XLSX | Cần convert → PDF bằng LibreOffice headless (**async, nặng**) → phase sau |
**API**: `GET /api/media/files/{id}/content` (hỗ trợ `Range`) → 200/206.
**Edge cases**: file lớn không Range → trình duyệt phải tải hết mới phát được; document convert fail → fallback "tải về để xem"; XSS từ HTML/SVG preview → serve từ **domain riêng** hoặc `Content-Disposition: attachment` + `X-Content-Type-Options: nosniff`.
**Trade-off**: LibreOffice convert tốn ~2–5s + RAM lớn → chạy container riêng, không nhét chung app server.

### C3.3 Grid/List view + Sort
**Mục đích**: đổi cách nhìn, sắp xếp theo nhu cầu.
**Logic**: **Sort phải làm ở server** (vì có phân trang — sort client-side chỉ sắp được trang hiện tại, sai về logic). View mode lưu ở `localStorage`, không cần backend.
**API**: `GET /api/media/files?folderId=&sort=name|createdAt|size|mimeType&dir=asc|desc&page=0&size=50`
**Edge cases**: sort theo tên có tiếng Việt → cần collation `utf8mb4_unicode_ci`; folder luôn đứng trước file (sort 2 tầng, không trộn chung); phân trang lệch khi có người upload giữa chừng → dùng **keyset pagination** (`WHERE (created_at, id) < (?, ?)`) nếu danh sách động mạnh.
**Trade-off**: offset pagination đơn giản nhưng chậm ở trang sâu; keyset nhanh nhưng không nhảy trang tuỳ ý. Recommend: **offset** cho phase đầu (folder hiếm khi > 10k file), chuyển keyset khi có vấn đề thật.

## C4. Xử lý file (Image processing)

### C4.1 Crop / Resize / Compress / Convert / Watermark
> Gộp 5 chức năng: **cùng pipeline**, chỉ khác operation + tham số.

**Mục đích**: sửa ảnh ngay trong hệ thống, không phải tải về sửa rồi upload lại.
**Logic**:
1. Nhận request kèm operation + params + `createNewVersion: boolean`.
2. Validate params (crop nằm trong khung ảnh, kích thước > 0, chất lượng 1–100).
3. **Async job**: tải blob gốc → apply operation → ghi blob mới → tạo `file_version` mới (`version_no + 1`) → cập nhật `current_version_id`.
4. Ảnh gốc **luôn được giữ** ở version cũ → user rollback được.

**API**:
```
POST /api/media/files/{id}/transform
{ operations: [ {type:"CROP", x,y,w,h}, {type:"RESIZE", width,height,fit:"cover"},
                {type:"COMPRESS", quality:80}, {type:"CONVERT", format:"webp"},
                {type:"WATERMARK", assetId, position:"BOTTOM_RIGHT", opacity:0.5} ],
  createNewVersion: true }
→ 202 { jobId }        (async)
GET /api/media/jobs/{jobId} → { status, resultFileId? }
```
**Data model**: `storage_blob` (blob mới), `file_version` (+1 row), `media_file.current_version_id/size_bytes/mime_type`.
**Edge cases**: crop vượt biên → clamp hoặc reject (recommend reject, tránh kết quả bất ngờ); convert HEIC cần thư viện native (ImageIO không hỗ trợ sẵn); PNG trong suốt → JPG mất alpha → cảnh báo hoặc tự phủ nền trắng; ảnh EXIF xoay → **phải apply orientation trước khi crop**, nếu không toạ độ crop lệch 90°; watermark lớn hơn ảnh → scale watermark theo tỉ lệ.
**Trade-off**:
- *Ghi đè vs tạo version mới*: **luôn tạo version mới** — thao tác phá huỷ không hoàn tác được là lỗi thiết kế nghiêm trọng.
- *ImageIO (sẵn có, chậm, tốn RAM) vs libvips/ImageMagick (nhanh 5–10×, cần cài native)*: bắt đầu ImageIO, chuyển libvips khi đủ tải.
- *Transform lúc request vs transform on-the-fly qua URL* (kiểu `?w=300&q=80`, Cloudinary-style): on-the-fly linh hoạt hơn nhiều cho responsive image nhưng cần cache layer + chống abuse (giới hạn preset). Recommend on-the-fly ở phase sau, chỉ cho **preset cố định**.

## C5. Metadata

### C5.1 File info + C5.2 EXIF
**Mục đích**: hiển thị thông tin file; đọc thông số ảnh (máy ảnh, GPS, ngày chụp).
**Logic**: info cơ bản lấy sẵn lúc upload. EXIF đọc **async** bằng `metadata-extractor` → lưu vào `file_metadata` dạng key-value → **strip GPS khi share công khai** (rò rỉ vị trí nhà user là sự cố bảo mật thật, không phải lo xa).
**API**: `GET /api/media/files/{id}` → FileDetailDto (kèm `metadata: {}`) · `GET /api/media/files/{id}/exif`.
**Data model**: `file_metadata (file_id, meta_key, meta_value)`.
**Edge cases**: EXIF sai định dạng → bỏ qua, đừng làm fail upload; EXIF khổng lồ (thumbnail nhúng) → chỉ lưu key cần thiết (whitelist), không dump tất cả.
**Trade-off**: key-value linh hoạt nhưng query kém; JSON column (MySQL 5.7+) gọn hơn nếu không cần filter theo EXIF. Recommend **key-value** nếu có ý định filter (vd "ảnh chụp bằng iPhone"), else JSON.

### C5.3 Tags / Labels
**Mục đích**: phân loại chéo folder (1 file nhiều tag).
**Logic**: gán/gỡ tag → thao tác trên `file_tag`. Tag tự tạo khi gõ tên mới (`getOrCreate`, giống `MediaFolderService.getOrCreateByName` đã có trong repo).
**API**: `PUT /api/media/files/{id}/tags { tagNames: [] }` (replace toàn bộ, idempotent) · `GET /api/media/tags`.
**Edge cases**: tag trùng khác hoa/thường → normalize lowercase; xoá tag đang dùng → xoá luôn liên kết (`ON DELETE CASCADE`); số tag/file → giới hạn ~20.
**Trade-off**: `PUT` replace toàn bộ (idempotent, dễ) vs `POST/DELETE` từng tag (tốn request). Recommend PUT.

## C6. Tìm kiếm

### C6.1 Search theo tên + C6.2 Filter (type, date, uploader, tag)
**Mục đích**: tìm file trong hàng nghìn file.
**Logic**: build query động (Specification/QueryDSL) → luôn kèm **điều kiện ACL** (chỉ trả file user được xem) → paginate.
```sql
WHERE status = 'ACTIVE'
  AND (:q IS NULL OR MATCH(name) AGAINST (:q IN BOOLEAN MODE))
  AND (:mime IS NULL OR mime_type LIKE :mimePrefix)
  AND (:from IS NULL OR created_at >= :from)
  AND (:uploader IS NULL OR owner_id = :uploader)
  AND (:folderId IS NULL OR folder_id = :folderId OR folder_path LIKE :subtree)  -- tìm trong subtree
```
**API**: `GET /api/media/search?q=&mimeType=&tags=&from=&to=&uploaderId=&folderId=&recursive=true&page=&size=`
**Edge cases**: `LIKE '%x%'` **không dùng được index** → với > ~100k file phải dùng FULLTEXT hoặc Elasticsearch; FULLTEXT MySQL bỏ từ < 3 ký tự (`ft_min_word_len`) và **hỗ trợ tiếng Việt kém** (không tách từ) → cân nhắc ngram parser; user gõ ký tự đặc biệt của boolean mode (`+`, `-`, `*`) → escape.
**Trade-off**: `LIKE` (đơn giản, đủ cho < 50k file) → FULLTEXT (trung bình) → Elasticsearch (mạnh nhất, thêm hạ tầng + đồng bộ). Recommend: **LIKE ở phase 1**, đo rồi mới nâng — đừng dựng Elasticsearch cho 5000 file.

## C7. Bảo mật & Phân quyền

### C7.1 Permission / ACL theo file & folder
**Mục đích**: kiểm soát ai xem/sửa/xoá được gì.
**Logic**:
1. Quyền **kế thừa từ folder cha** (giống hệ điều hành). Check quyền file X:
   - Parse `folder.path` của X → ra danh sách ancestor id.
   - `SELECT * FROM acl_entry WHERE (resource=FILE AND id=X) OR (resource=FOLDER AND id IN (ancestors)) AND subject IN (user, roles, PUBLIC)`
   - OR tất cả `permission_mask` lại → AND với quyền cần.
2. Owner luôn có full quyền (không cần row ACL).
3. Cache kết quả theo request (`@RequestScope`) — 1 request list 50 file không nên chạy 50 lần query ACL.
**API**: `GET/PUT /api/media/{type}/{id}/permissions`
**Data model**: `acl_entry`.
**Edge cases**: file ở root (không folder) → chỉ ACL trực tiếp; xoá user còn ACL → cascade xoá entry; **deny override** (cấm thắng cho phép)? → recommend **không làm deny** ở phase đầu (mô hình allow-only đơn giản, đủ dùng, ít bug logic).
**Trade-off**: check ACL từng file khi list N file = N query → **phải batch**: 1 query lấy hết ACL liên quan rồi lọc trong memory. Đây là điểm dễ gây N+1 nhất của toàn module.

### C7.2 Share link (public/private) + expiring + password
> Gộp 3 chức năng: cùng 1 entity, chỉ khác thuộc tính bật/tắt.

**Mục đích**: chia sẻ cho người không có tài khoản, có kiểm soát.
**Logic**:
1. Tạo link → sinh token **random 32 bytes** (`SecureRandom`, base64url) — **không dùng id tuần tự hay UUIDv1** (đoán được).
2. Truy cập `/s/{token}`: endpoint **public** (permitAll) → tra token → check `revoked_at IS NULL` → check `expires_at` → check `download_count < max_downloads` → nếu có mật khẩu: yêu cầu nhập, so BCrypt → cấp **session ngắn hạn cho riêng link đó** (JWT 15 phút scope = shareId), không bắt nhập lại mỗi lần tải.
3. Mỗi lần tải: `UPDATE download_count = download_count + 1` (atomic).
**API**:
```
POST   /api/media/files/{id}/share  { expiresAt?, password?, maxDownloads?, allowUpload? } → 201 { url, token }
DELETE /api/media/share/{id}                                        → 204 (revoke)
GET    /api/public/share/{token}                                    → 200 metadata | 401 cần password
POST   /api/public/share/{token}/auth  { password }                 → 200 { accessToken }
GET    /api/public/share/{token}/download                           → 200 stream
```
**Data model**: `share_link`.
**Edge cases**:
| Tình huống | Xử lý |
|---|---|
| Brute-force password/token | **Rate limit theo IP + theo token** (bắt buộc, không phải tuỳ chọn) |
| File bị xoá nhưng link còn | Check status file khi truy cập → 404 |
| Link folder có chứa file user không được xem | Share link **bỏ qua ACL nội bộ** — ai có link thì xem được cả folder. Phải nói rõ trên UI |
| Timing attack khi so token | Dùng so sánh constant-time |
| Link bị index bởi Google | Trả header `X-Robots-Tag: noindex` |
**Trade-off**: token trong URL sẽ nằm trong lịch sử trình duyệt/referrer → thêm `Referrer-Policy: no-referrer` cho trang share.

## C8. Versioning

### C8.1 Version history khi replace + C8.2 Rollback
**Mục đích**: thay file mới mà không mất bản cũ; quay lại khi thay nhầm.
**Logic**:
- **Replace**: upload với `replaceFileId` → chạy flow upload bình thường → tạo `file_version` mới (`version_no = max + 1`) → cập nhật `current_version_id` + denorm size/mime. Blob cũ **giữ nguyên**, `ref_count` không giảm.
- **Rollback về v2**: **không xoá v3**, mà tạo **v4 trỏ cùng blob với v2** (lịch sử tuyến tính, audit rõ ràng, hoàn tác được cả thao tác rollback).
**API**:
```
GET  /api/media/files/{id}/versions              → [{ versionNo, size, createdBy, createdAt, note }]
POST /api/media/files/{id}/versions/{no}/restore → 200 FileDto
GET  /api/media/files/{id}/versions/{no}/content → stream bản cũ
DELETE /api/media/files/{id}/versions/{no}       → 204 (dọn bớt, cần quyền)
```
**Data model**: `file_version`, `storage_blob.ref_count`, `media_file.current_version_id`.
**Edge cases**: replace bằng file khác mime (JPG → PDF) → cho phép nhưng cảnh báo; số version vô hạn → **giới hạn giữ N version gần nhất** (vd 10), tự dọn cũ hơn (giảm `ref_count`); rollback đồng thời 2 người → optimistic lock (`@Version`) trên `media_file`.
**Trade-off**: giữ mọi version tốn disk (một file 100MB sửa 20 lần = 2GB) → chính sách retention phải có từ đầu, đừng để user tự phát hiện khi hết đĩa.

## C9. Storage

### C9.1 Local adapter + C9.2 Cloud adapter
**Mục đích**: chạy local lúc dev/nhỏ, lên cloud khi lớn, không sửa business code.
**Logic**: `StorageAdapter` chọn impl qua config (`app.storage.provider=LOCAL|S3`). Key sinh theo `yyyy/MM/{uuid}` (chia thư mục theo tháng — tránh 1 thư mục chứa triệu file làm chậm filesystem).
- Local: `Files.copy` + serve qua `ResourceHandler` (repo hiện tại đã làm đúng).
- S3: `PutObject` + **presigned URL** để FE tải thẳng từ S3, không qua app server.
**API**: không expose ra ngoài (internal interface).
**Data model**: `storage_blob.storage_provider/storage_key` — cho phép **migrate từng blob**, 2 provider cùng tồn tại.
**Edge cases**: disk đầy → `IOException` → trả 507 rõ ràng, không để 500 chung chung; S3 eventual consistency (giờ đã strong cho PUT mới); presigned URL hết hạn giữa lúc user xem → FE tự refresh khi 403.
**Trade-off**: **quan trọng nhất** — nếu serve file qua app server (`@GetMapping` stream) thì khi lên S3 mọi byte vẫn chạy qua server (tốn băng thông + thread). Nên thiết kế FE **luôn dùng URL do backend cấp** (`thumbnailUrl`, `contentUrl` trong DTO) — local trả `/uploads/...`, S3 trả presigned URL. FE không cần biết khác biệt.

### C9.3 Storage quota theo user
**Mục đích**: chặn 1 user chiếm hết đĩa.
**Logic**: **atomic conditional update**, không read-then-write:
```sql
UPDATE storage_quota
SET used_bytes = used_bytes + :size
WHERE user_id = :uid AND used_bytes + :size <= quota_bytes;
-- affectedRows == 0 → vượt quota → 507
```
**API**: `GET /api/media/quota` → `{ used, total, percentage }`.
**Edge cases**: 2 upload song song → conditional update xử lý đúng (đây chính là lý do không dùng read-then-write); số liệu lệch do bug → job đối soát định kỳ (`SUM(size) FROM media_file` vs `used_bytes`); dedupe làm 2 user chung 1 blob → **tính quota theo logical size** (mỗi user tính đủ), đơn giản và công bằng với user.
**Trade-off**: tính quota logical (dễ hiểu, tổng > disk thật) vs physical (chính xác, user khó hiểu vì upload file giống người khác thì không tốn dung lượng). Recommend **logical**.

## C10. Chức năng khác

### C10.1 Download single + C10.2 Download ZIP
**Logic**:
- Single: check ACL DOWNLOAD → local: stream + `Content-Disposition`; S3: 302 → presigned URL. Hỗ trợ Range.
- ZIP: **stream ZIP trực tiếp** (`ZipOutputStream` ghi thẳng vào response, không tạo file tạm) nếu tổng < ~50MB; lớn hơn → async job tạo file ZIP → trả `jobId` → user tải khi xong.
**API**: `GET /api/media/files/{id}/download` · `POST /api/media/download/zip { fileIds, folderIds }` → 200 stream | 202 `{jobId}`.
**Edge cases**: trùng tên trong ZIP → thêm hậu tố; tên file Unicode → set UTF-8 flag trong ZIP entry (không thì Windows hiện lỗi font); ZIP > 4GB → cần Zip64; user huỷ giữa chừng → đóng stream, không để thread treo.
**Trade-off**: stream (không tốn disk, không biết trước `Content-Length` → không có progress bar) vs tạo file trước (có progress, tốn disk + chờ). Recommend theo ngưỡng như trên.

### C10.3 Virus / malware scan
**Logic**: async sau upload → file ở `PENDING_SCAN` → gửi tới ClamAV (clamd TCP/INSTREAM) → `CLEAN` → `ACTIVE`; `INFECTED` → `QUARANTINED` + thông báo owner + **chặn download/share**.
**API**: nội bộ; trạng thái lộ qua `media_file.status` trong DTO.
**Edge cases**: ClamAV chết → file kẹt `PENDING_SCAN` mãi → có timeout + retry + alert; file lớn > giới hạn clamd (`StreamMaxLength`) → cấu hình hoặc bỏ qua có kiểm soát; false positive → cần cơ chế admin gỡ quarantine.
**Trade-off**: chặn download tới khi scan xong (an toàn, UX chậm) vs cho tải ngay (nhanh, rủi ro). Recommend: **cho owner xem/tải, chặn share public** cho tới khi CLEAN — cân bằng hợp lý.

### C10.4 Duplicate detection (hash)
**Logic**: SHA-256 tính **trong lúc stream upload** (`DigestInputStream`, không đọc file 2 lần) → tra `storage_blob.sha256` → nếu trùng: tái dùng blob, `ref_count++`, báo FE "file này đã tồn tại ở X".
**API**: `POST /api/media/files/check-duplicate { sha256, size }` → `{ exists, existingFile? }` — FE hash trước bằng WebCrypto để **bỏ qua upload hoàn toàn** nếu đã có (tiết kiệm băng thông, giống Google Drive).
**Edge cases**: hash collision SHA-256 → xem như không thể (2^-128); file rỗng → hash cố định, vẫn hợp lệ; FE hash file 1GB làm treo UI → dùng Web Worker + streaming.
**Trade-off**: dedupe toàn hệ thống (tiết kiệm nhất, nhưng user A xoá file có thể ảnh hưởng user B nếu `ref_count` sai) vs dedupe theo từng user (an toàn hơn, ít tiết kiệm hơn). Recommend **toàn hệ thống + `ref_count` chuẩn + grace period trước khi xoá vật lý**.

### C10.5 Audit log
**Logic**: `ApplicationEventPublisher` → `@Async` listener ghi `audit_log` (append-only). Log: UPLOAD, DOWNLOAD, RENAME, MOVE, DELETE, RESTORE, SHARE_CREATE, SHARE_ACCESS, PERMISSION_CHANGE.
**API**: `GET /api/media/audit?resourceId=&actorId=&action=&from=&to=` (chỉ admin).
**Edge cases**: ghi log fail **không được** làm hỏng nghiệp vụ chính (listener bắt hết exception, chỉ log warn); bảng phình to → partition theo tháng + archive; log `DOWNLOAD` của public share → ghi IP, không có user id.
**Trade-off**: log đồng bộ (không mất log, chậm request) vs async (nhanh, có thể mất khi crash). Với audit **tuân thủ/pháp lý** thì phải đồng bộ; với audit vận hành thông thường → **async**.

---

# Phần D — Roadmap triển khai

| Phase | Chức năng | Lý do xếp phase | Độ phức tạp |
|---|---|---|---|
| **P0 — Nền móng**<br>*(bắt buộc trước mọi thứ)* | `StorageAdapter` + local impl · schema `storage_blob`/`media_file`/`file_version`/`folder` · `FileValidator` chain · hash + dedupe · quota | Đây là phần **retrofit sau rất đau**. Đặc biệt tách blob và materialized path — làm sau = migrate toàn bộ dữ liệu | **High** |
| **P1 — MVP dùng được** | Upload single/multi + drag&drop + paste · folder nested (CRUD) · rename/move · soft delete + trash + restore · list + sort + phân trang · thumbnail ảnh (async) · download single | Đủ để thay thế cách làm thủ công. Không có cái này thì module vô dụng | **Medium** |
| **P2 — Trải nghiệm** | Chunked upload · progress · preview viewer (ảnh/video/PDF) · search + filter · tags · grid/list view · copy · download ZIP | Nâng UX, phụ thuộc P1. Chunked cần `MultipartStorageAdapter` từ P0 | **Medium–High** |
| **P3 — Bảo mật & cộng tác** | ACL file/folder · share link (public/expiring/password) · audit log · virus scan | Phụ thuộc ACL — mà ACL phải có trước share link. Virus scan cần hạ tầng ClamAV | **High** |
| **P4 — Nâng cao** | Versioning + rollback · image processing (crop/resize/compress/convert/watermark) · EXIF · S3 adapter + migrate | Versioning dễ vì blob đã tách từ P0. S3 adapter chỉ là 1 impl mới nếu P0 làm đúng | **Medium** |
| **P5 — Quy mô lớn** | Elasticsearch search · on-the-fly image transform + CDN · document preview (LibreOffice) · partition audit log | Chỉ làm khi có **số liệu thật** chứng minh cần. Tối ưu sớm ở đây là lãng phí | **High** |

**Nguyên tắc xuyên suốt**: P0 làm đúng thì P4 (versioning, S3) gần như miễn phí. P0 làm ẩu thì P4 phải viết lại từ đầu + migrate dữ liệu production.

---

# Phần E — Rủi ro kỹ thuật

| Rủi ro | Ảnh hưởng | Cách phòng tránh |
|---|---|---|
| **DB và file system lệch nhau** (orphan file / record trỏ file không tồn tại) | Tốn disk âm thầm; user mở file ra lỗi 404 | Thứ tự cố định: ghi file → commit DB. Janitor job đối soát 2 chiều hằng đêm. **Không bao giờ** commit DB trước |
| **Gọi I/O ngoài trong `@Transactional`** (S3, ClamAV, TinyPNG) | Cạn connection pool → **sập toàn hệ thống**, không chỉ module media | Code review bắt buộc: transaction chỉ chứa thao tác DB. Đặt `spring.transaction.default-timeout` ngắn để lộ sớm |
| **N+1 khi check ACL lúc list file** | List 50 file = 50+ query → trang tải 3–5s | Batch load ACL 1 query rồi lọc in-memory; cache theo request scope |
| **`ref_count` sai** → xoá nhầm blob còn người dùng | **Mất dữ liệu không cứu được** — rủi ro nghiêm trọng nhất | `ref_count` chỉ đổi trong transaction; grace period 24h trước khi xoá vật lý; job đối soát `ref_count` vs `COUNT(file_version)` |
| **Decode ảnh khổng lồ → OOM** | App server chết, ảnh hưởng mọi user | Đọc header lấy kích thước trước khi decode; chặn > 50MP; chạy xử lý ảnh ở worker riêng, giới hạn heap |
| **Path traversal** (`../../etc/passwd`) | Đọc/ghi file ngoài thư mục cho phép | `storage_key` = UUID do server sinh, **không bao giờ** ghép từ input user; `normalize()` + kiểm tra parent (repo hiện tại đã làm đúng) |
| **Magic bytes bị bỏ qua, chỉ tin extension** | Upload webshell/mã độc | Validator chain bắt buộc có bước magic bytes; SVG serve dạng attachment |
| **Brute-force share token / password** | Rò rỉ file riêng tư | Token 32 bytes random; rate limit theo IP + token; so sánh constant-time |
| **Rò rỉ GPS trong EXIF khi share công khai** | Lộ vị trí nhà/nơi làm việc của user | Strip GPS (và toàn bộ EXIF) ở bản render cho public share |
| **Move folder subtree lớn khoá bảng lâu** | Timeout, user khác bị treo | Giới hạn depth; nếu subtree > 10k node → chuyển async + đánh dấu trạng thái `MOVING` |
| **Trash chiếm quota nhưng user không biết** | User tưởng đã xoá mà vẫn hết dung lượng | Hiện rõ dung lượng trash trên UI + nhắc tự động xoá sau N ngày |
| **Search `LIKE '%x%'` không dùng index** | Chậm dần theo dữ liệu, khó nhận ra tới lúc quá muộn | Đo p95 query time từ đầu; đặt ngưỡng (vd > 500ms) là tín hiệu chuyển FULLTEXT/ES |
| **Upload không giới hạn tốc độ** | 1 user làm đầy đĩa / bão hoà băng thông | Rate limit theo user (repo đã có `UploadRateLimiter`) + quota + giới hạn kích thước |
| **Không có ngưỡng retention cho version** | File sửa nhiều lần phình dung lượng gấp chục lần | Giới hạn N version/file ngay từ khi làm P4, không để "tính sau" |

---

## Tóm tắt 5 quyết định thiết kế quan trọng nhất

1. **Tách `storage_blob` khỏi `media_file` ngay từ P0** — mở khoá dedupe + versioning + migrate storage, và gần như không thể retrofit sau.
2. **`storage_key` là UUID, độc lập tên/folder** — rename và move trở thành thao tác DB thuần, 0 I/O.
3. **Materialized path cho folder tree** — breadcrumb và ACL kế thừa không cần đệ quy.
4. **Mọi thứ chậm hoặc gọi ra ngoài đều async** (thumbnail, scan, nén, zip lớn, hard delete) — giữ transaction ngắn, response nhanh.
5. **Ghi file trước, commit DB sau; không bao giờ ngược lại** — orphan file dọn được, record hỏng thì không.
