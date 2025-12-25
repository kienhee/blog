# Tài liệu: Chức năng Đăng bài theo lịch (Scheduled Posts)

## Tổng quan

Chức năng **Đăng bài theo lịch** cho phép người dùng lên lịch đăng bài viết vào một thời điểm cụ thể trong tương lai. Hệ thống sẽ tự động đăng bài viết khi đến thời gian đã đặt và gửi email thông báo cho người tạo bài viết.

**Đặc điểm chính:**

-   Bài viết có trạng thái riêng: **"Lên lịch" (scheduled)**
-   Input chọn thời gian chỉ hiển thị khi chọn trạng thái "Lên lịch"
-   Tự động đăng bài khi đến thời gian đã đặt
-   Gửi email thông báo cho tác giả

## Kiến trúc tổng quan

Chức năng này được triển khai qua các thành phần sau:

1. **Database**: Cột `scheduled_at` (timestamp, nullable) và enum `status` với giá trị `'scheduled'`
2. **Model**: Cast `scheduled_at` thành `datetime`, constant `STATUS_SCHEDULED`
3. **Command**: `PublishScheduledPosts` - Xử lý đăng bài tự động
4. **Schedule**: Chạy command mỗi phút
5. **Mail**: `PostPublishedNotification` - Email thông báo khi bài được đăng
6. **Controller**: Xử lý lưu và cập nhật `scheduled_at` với logic status "scheduled"
7. **Views**: Form input với Flatpickr (chỉ hiện khi status = "scheduled")
8. **JavaScript**: Xử lý logic ẩn/hiện input và tự động đổi status

---

## 1. Database Layer

### 1.1. Migration

Bảng `posts` có cột `scheduled_at` và enum `status`:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    // ... các cột khác
    $table->enum('status', ['draft','scheduled', 'published'])->default('published');
    $table->timestamp('scheduled_at')->nullable();
    // ...
});
```

**Lưu ý:**

-   Enum `status` có 3 giá trị: `'draft'`, `'scheduled'`, `'published'`
-   Cột `scheduled_at` là `nullable` vì không phải tất cả bài viết đều có lịch đăng
-   `scheduled_at` được đặt sau cột `status` để dễ quản lý

---

## 2. Model Layer

### 2.1. Post Model

Thêm `scheduled_at` vào `$fillable`, cast thành `datetime`, và thêm constant `STATUS_SCHEDULED`:

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
        'content',
        'status',
        'scheduled_at', // Thêm scheduled_at
        // ... các fields khác
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime', // Cast thành Carbon datetime
        ];
    }

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled'; // Trạng thái mới
    const STATUS_PUBLISHED = 'published';
}
```

**Lưu ý:**

-   Cast `scheduled_at` thành `datetime` để Laravel tự động xử lý với Carbon
-   Model có relationship `user()` để lấy thông tin người tạo bài viết

---

## 3. Repository Layer

### 3.1. Thêm `scheduled_at` vào gridData

Trong `PostRepository::gridData()`, thêm `scheduled_at` vào select:

```php
$query->select([
    'posts.id',
    'posts.title as title',
    'posts.status as status',
    'posts.scheduled_at as scheduled_at', // Thêm scheduled_at
    'posts.created_at as created_at',
    // ... các fields khác
]);
```

### 3.2. Hiển thị trạng thái Scheduled

Trong `PostRepository::renderDataTables()`, cập nhật column `status_html`:

```php
->addColumn('status_html', function ($row) {
    $status = $row->status;
    $scheduledAt = isset($row->scheduled_at) ? $row->scheduled_at : null;

    if ($status == 'published') {
        return '<span class="badge bg-label-success">Xuất bản</span>';
    } elseif ($status == 'scheduled' && $scheduledAt && Carbon::parse($scheduledAt) > now()) {
        // Bài viết đã được lên lịch
        $scheduledDate = Carbon::parse($scheduledAt)->format('d/m/Y H:i');
        return '<span class="badge bg-label-warning">Lên lịch</span> <small class="text-muted d-block">' . $scheduledDate . '</small>';
    } else {
        return '<span class="badge bg-label-danger">Bản nháp</span>';
    }
})
```

**Lưu ý:**

-   Hiển thị badge "Lên lịch" với thời gian nếu `status = 'scheduled'` và `scheduled_at > now()`
-   Format hiển thị: `d/m/Y H:i` (ví dụ: 12/12/2025 14:30)

---

## 4. Command Layer

### 4.1. PublishScheduledPosts Command

Command để tự động đăng các bài viết đã đến thời gian:

```php
<?php

namespace App\Console\Commands;

use App\Mail\PostPublishedNotification;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';
    protected $description = 'Publish scheduled posts and send notifications to authors';

    public function handle()
    {
        $now = now();

        // Lấy các bài viết có scheduled_at đã đến và đang ở trạng thái scheduled
        $scheduledPosts = Post::where('status', Post::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->with('user')
            ->get();

        if ($scheduledPosts->isEmpty()) {
            $this->info('Không có bài viết nào cần đăng.');
            return Command::SUCCESS;
        }

        $count = 0;

        foreach ($scheduledPosts as $post) {
            // Cập nhật status thành published
            $post->status = Post::STATUS_PUBLISHED;
            $post->scheduled_at = null; // Xóa scheduled_at sau khi đăng
            $post->save();

            // Gửi email thông báo cho người tạo bài viết
            if ($post->user && $post->user->email) {
                try {
                    Mail::to($post->user->email)->send(
                        new PostPublishedNotification($post)
                    );
                } catch (\Exception $e) {
                    $this->error("Không thể gửi email cho bài viết ID {$post->id}: " . $e->getMessage());
                }
            }

            $count++;
            $this->info("Đã đăng bài viết: {$post->title} (ID: {$post->id})");
        }

        $this->info("Hoàn thành! Đã đăng {$count} bài viết.");

        return Command::SUCCESS;
    }
}
```

**Lưu ý:**

-   Chỉ lấy các bài viết có `status = 'scheduled'` và `scheduled_at <= now()`
-   Sau khi đăng, set `status = 'published'` và `scheduled_at = null`
-   Gửi email thông báo nhưng không throw exception nếu email fail (chỉ log error)
-   Eager load `user` relationship để tránh N+1 query

### 4.2. Đăng ký Command vào Schedule

Trong `routes/console.php`:

```php
<?php

use Illuminate\Support\Facades\Schedule;

// Đăng bài theo lịch - chạy mỗi phút
Schedule::command('posts:publish-scheduled')->everyMinute();
```

**Lưu ý:**

-   Command được đăng ký chạy mỗi phút để đảm bảo bài viết được đăng đúng thời gian
-   **Development**: Chạy `php artisan schedule:work` để scheduler chạy ở chế độ nền
-   **Production**: Cấu hình cron job để gọi `php artisan schedule:run` mỗi phút
-   **Testing**: Chạy trực tiếp `php artisan posts:publish-scheduled` để test một lần

---

## 5. Mail Layer

### 5.1. PostPublishedNotification Mailable

Tạo Mailable để gửi email thông báo:

```php
<?php

namespace App\Mail;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PostPublishedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Post $post
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Bài viết \"{$this->post->title}\" đã được đăng",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.post.post-published',
            with: [
                'post' => $this->post,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
```

### 5.2. Email Template

File `resources/views/emails/post-published.blade.php`:

```blade
@extends('emails.layouts.master')

@section('title', 'Bài viết đã được đăng')

@section('content')
    <p style="margin:0 0 12px; color:#1F2937; font-size:18px; font-weight:700;">
        Xin chào {{ $post->user->full_name ?? $post->user->email }},
    </p>

    <p style="margin:0 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Bài viết của bạn <strong>"{{ $post->title }}"</strong> đã được đăng thành công!
    </p>

    <div style="margin:16px 0; padding:14px; background:#F0FDF4; border-left:4px solid #22C55E; border-radius:6px;">
        <p style="margin:0; color:#166534; font-size:14px; font-weight:600;">✓ Bài viết đã được xuất bản</p>
    </div>

    <p style="margin:16px 0 12px; color:#6B7280; font-size:15px; line-height:1.5;">
        Bạn có thể xem bài viết của mình tại:
    </p>

    <p style="margin:0 0 20px;">
        <a href="{{ url('/posts/' . $post->slug) }}" style="display:inline-block; padding:12px 20px; border-radius:8px; text-decoration:none; background:#FF6A3D; color:#fff; font-weight:700;">
            Xem bài viết
        </a>
    </p>

    <p style="margin:0; color:#9CA3AF; font-size:13px; line-height:1.4;">
        Thời gian đăng: {{ now()->format('d/m/Y H:i:s') }}
    </p>
@endsection
```

**Lưu ý:**

-   Sử dụng inline CSS để tương thích với email clients
-   Hiển thị link đến bài viết công khai
-   Hiển thị thời gian đăng chính xác

---

## 6. Controller Layer

### 6.1. Store Method

Trong `PostController::store()`:

```php
public function store(StoreRequest $request)
{
    try {
        $data = [
            'title' => $request->input('title'),
            'slug' => $request->input('slug'),
            'status' => $request->input('status'),
            'category_id' => $request->input('category_id'),
            'content' => $request->input('content'),
            'thumbnail' => $request->input('thumbnail'),
            'allow_comment' => $request->boolean('allow_comment'),
            'description' => $request->input('description'),
            'user_id' => auth()->id(),
        ];

        // Xử lý scheduled_at và status
        if ($request->filled('scheduled_at')) {
            $data['scheduled_at'] = $request->input('scheduled_at');
            // Nếu có scheduled_at, đảm bảo status là "scheduled"
            if ($data['status'] !== Post::STATUS_SCHEDULED) {
                $data['status'] = Post::STATUS_SCHEDULED;
            }
        } else {
            // Nếu không có scheduled_at nhưng status là "scheduled", đổi về "draft"
            if ($data['status'] === Post::STATUS_SCHEDULED) {
                $data['status'] = Post::STATUS_DRAFT;
            }
            $data['scheduled_at'] = null;
        }

        // Nếu status là "published", không được có scheduled_at
        if ($data['status'] === Post::STATUS_PUBLISHED) {
            $data['scheduled_at'] = null;
        }

        $this->postRepository->createWithHashtags($data, $request->input('hashtags'));

        return back()->with('success', 'Thêm mới thành công');
    } catch (\Throwable $e) {
        return back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
    }
}
```

**Lưu ý:**

-   Nếu có `scheduled_at`, **bắt buộc** phải đặt `status = 'scheduled'`
-   Nếu không có `scheduled_at` nhưng `status = 'scheduled'`, đổi về `'draft'`
-   Không thể có bài viết với `status = 'published'` và `scheduled_at` cùng lúc

### 6.2. Update Method

Trong `PostController::update()`:

```php
public function update(UpdateRequest $request, $id)
{
    try {
        $existingPost = $this->postRepository->findById($id);
        if (!$existingPost) {
            return back()->with('error', 'Bài viết không tồn tại');
        }

        $data = [
            'title' => $request->input('title'),
            'slug' => $request->input('slug'),
            'status' => $request->input('status'),
            'category_id' => $request->input('category_id'),
            'content' => $request->input('content'),
            'thumbnail' => $request->input('thumbnail'),
            'allow_comment' => $request->boolean('allow_comment'),
            'description' => $request->input('description'),
            'user_id' => auth()->id(),
        ];

        // Xử lý scheduled_at và status
        $postModel = Post::find($id);
        if ($postModel) {
            $isPublished = $postModel->status === Post::STATUS_PUBLISHED;
            $scheduledTimePassed = $postModel->scheduled_at && $postModel->scheduled_at <= now();
            $canEditScheduled = !$isPublished && !$scheduledTimePassed;

            if ($canEditScheduled) {
                // Chỉ cho phép update scheduled_at nếu bài viết chưa được đăng
                if ($request->filled('scheduled_at')) {
                    $data['scheduled_at'] = $request->input('scheduled_at');
                    // Nếu có scheduled_at, đảm bảo status là "scheduled"
                    if ($data['status'] !== Post::STATUS_SCHEDULED) {
                        $data['status'] = Post::STATUS_SCHEDULED;
                    }
                } else {
                    // Nếu không có scheduled_at, xóa scheduled_at cũ
                    $data['scheduled_at'] = null;
                    // Nếu status là "scheduled" nhưng không có scheduled_at, đổi về "draft"
                    if ($data['status'] === Post::STATUS_SCHEDULED) {
                        $data['status'] = Post::STATUS_DRAFT;
                    }
                }
            } else {
                // Nếu không thể edit, giữ nguyên scheduled_at hiện tại (không update)
                unset($data['scheduled_at']);
            }

            // Nếu status là "published", không được có scheduled_at
            if ($data['status'] === Post::STATUS_PUBLISHED) {
                $data['scheduled_at'] = null;
            }
        }

        $post = $this->postRepository->updateWithHashtags($id, $data, $request->input('hashtags'));

        if (!$post) {
            return back()->with('error', 'Bài viết không tồn tại');
        }

        return back()->with('success', 'Cập nhật thành công');
    } catch (\Throwable $e) {
        return back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
    }
}
```

**Lưu ý:**

-   Chỉ cho phép chỉnh sửa `scheduled_at` khi:
    -   Bài viết chưa được đăng (`status !== 'published'`)
    -   Thời gian `scheduled_at` chưa đến (`scheduled_at > now()`)
-   Nếu có `scheduled_at`, luôn đặt `status = 'scheduled'`
-   Nếu không có `scheduled_at` nhưng `status = 'scheduled'`, đổi về `'draft'`
-   Nếu không thể edit, giữ nguyên `scheduled_at` hiện tại

### 6.3. Edit Method

Trong `PostController::edit()`, cần truyền thêm `$post` và `$canEditScheduledAt`:

```php
public function edit($id)
{
    $grid = $this->postRepository->gridData();
    $data = $this->postRepository->getPostById($id);
    $post = Post::find($id); // Lấy model để check scheduled_at

    $hashtags = $this->hashtagRepository->getHashTagByType('post');
    $statusLabels = $this->postRepository->getStatusLabel();
    $categoriesData = $this->categoryRepository->getCategoryByType('post');
    $categories = $this->categoryRepository->buildTree($categoriesData->toArray());

    // Kiểm tra xem có thể chỉnh sửa scheduled_at không
    // Chỉ cho phép edit nếu status là draft hoặc scheduled (chưa đăng)
    $canEditScheduledAt = $post &&
                          ($post->status === Post::STATUS_DRAFT || $post->status === Post::STATUS_SCHEDULED) &&
                          (!$post->scheduled_at || $post->scheduled_at > now());

    return view('admin.modules.post.edit', data: compact('data', 'hashtags', 'statusLabels', 'categories', 'post', 'canEditScheduledAt'));
}
```

---

## 7. Request Validation Layer

### 7.1. StoreRequest

Thêm validation rule cho `scheduled_at` và cập nhật enum `status`:

```php
public function rules(): array
{
    return [
        'title' => 'required|string|min:6',
        'slug' => 'required|string|unique:posts,slug',
        'status' => 'required|in:draft,scheduled,published', // Thêm 'scheduled'
        'category_id' => 'required|exists:categories,id',
        'hashtags' => 'nullable|array',
        'hashtags.*' => 'exists:hash_tags,id',
        'content' => 'required|string',
        'thumbnail' => 'required|string',
        'allow_comment' => 'nullable|in:0,1',
        'description' => 'nullable|string|max:255',
        'scheduled_at' => [
            'nullable',
            'date',
            Rule::when(
                fn ($input) => !empty($input['scheduled_at']),
                ['after:now']
            )
        ],
    ];
}
```

### 7.2. UpdateRequest

Tương tự StoreRequest:

```php
public function rules(): array
{
    $id = $this->route('id');
    return [
        'title' => 'required|string|min:6',
        'slug' => 'required|string|unique:posts,slug,'.$id,
        'status' => 'required|in:draft,scheduled,published', // Thêm 'scheduled'
        'category_id' => 'required|exists:categories,id',
        'hashtags' => 'nullable|array',
        'hashtags.*' => 'exists:hash_tags,id',
        'content' => 'required|string',
        'thumbnail' => 'required|string',
        'allow_comment' => 'nullable|in:0,1',
        'description' => 'nullable|string|max:255',
        'scheduled_at' => [
            'nullable',
            'date',
            Rule::when(
                fn ($input) => !empty($input['scheduled_at']),
                ['after:now']
            )
        ],
    ];
}
```

**Lưu ý:**

-   Validation `after:now` chỉ áp dụng khi `scheduled_at` có giá trị
-   Format nhận vào: `Y-m-d H:i` (ví dụ: "2025-12-12 14:30")
-   Enum `status` bao gồm 3 giá trị: `'draft'`, `'scheduled'`, `'published'`

---

## 8. View Layer

### 8.1. Create Form

Trong `resources/views/admin/modules/post/create.blade.php`:

```blade
<div class="mb-3" id="scheduled_at_field" style="display: none;">
    <label class="form-label" for="scheduled_at">Đăng bài theo lịch
        <span class="text-muted">(Bắt buộc)</span>
    </label>
    <input type="text"
        class="form-control"
        id="scheduled_at"
        name="scheduled_at"
        value="{{ old('scheduled_at') }}"
        placeholder="Chọn ngày và giờ đăng bài" />
    <small class="text-muted">Chọn thời gian đăng bài tự động. Bài viết sẽ tự động đăng vào thời gian đã chọn.</small>
</div>
```

**Lưu ý:**

-   Input được ẩn mặc định (`display: none`)
-   Chỉ hiện khi user chọn status = "scheduled" (xử lý bằng JavaScript)
-   Label hiển thị "(Bắt buộc)" vì khi chọn status "scheduled" thì phải có `scheduled_at`

### 8.2. Edit Form

Trong `resources/views/admin/modules/post/edit.blade.php`:

```blade
@php
    $scheduledAtValue = old(
        'scheduled_at',
        $post->scheduled_at ? $post->scheduled_at->format('Y-m-d H:i') : '',
    );
    $isPublished = $post && $post->status === \App\Models\Post::STATUS_PUBLISHED;
    $isScheduled = $post && $post->status === \App\Models\Post::STATUS_SCHEDULED;
    $scheduledTimePassed = $post && $post->scheduled_at && $post->scheduled_at <= now();
    $isDisabled = $isPublished || $scheduledTimePassed;
@endphp
<div class="mb-3" id="scheduled_at_field" style="display: {{ $isScheduled ? 'block' : 'none' }};">
    <label class="form-label" for="scheduled_at">Đăng bài theo lịch
        <span class="text-muted">(Bắt buộc)</span>
    </label>
    @if ($isDisabled)
        <input type="hidden" name="scheduled_at" value="{{ $scheduledAtValue }}" />
    @endif
    <input type="text"
        class="form-control @if ($isDisabled) bg-light @endif"
        id="scheduled_at"
        name="scheduled_at"
        value="{{ $scheduledAtValue }}"
        placeholder="Chọn ngày và giờ đăng bài"
        @if ($isDisabled) disabled readonly @endif />
    @if ($isPublished)
        <small class="text-success d-block mt-1">
            <i class="bx bx-check-circle"></i> Bài viết đã được đăng
        </small>
    @elseif($scheduledTimePassed && $post->scheduled_at)
        <small class="text-info d-block mt-1">
            <i class="bx bx-info-circle"></i> Đã đến thời gian đăng bài. Bài viết sẽ được đăng tự động.
        </small>
    @elseif($isScheduled && $post->scheduled_at)
        <small class="text-warning d-block mt-1">
            <i class="bx bx-time"></i> Trạng thái: <strong>Lên lịch</strong> - Sẽ đăng vào: {{ $post->scheduled_at->format('d/m/Y H:i') }}
        </small>
    @else
        <small class="text-muted d-block mt-1">
            Chọn thời gian đăng bài tự động. Bài viết sẽ tự động đăng vào thời gian đã chọn.
        </small>
    @endif
</div>
```

**Lưu ý:**

-   Input hiển thị nếu `$isScheduled = true`, ẩn nếu không
-   Input bị disable khi:
    -   Bài viết đã được đăng (`status = 'published'`)
    -   Thời gian `scheduled_at` đã qua (`scheduled_at <= now()`)
-   Khi disabled, sử dụng hidden input để giữ giá trị
-   Hiển thị thông báo phù hợp với trạng thái

### 8.3. Include Flatpickr Assets

Thêm vào `@push('styles')` và `@push('scripts')`:

```blade
@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
@endpush
```

---

## 9. JavaScript Layer

### 9.1. Khởi tạo Flatpickr và Logic Ẩn/Hiện

Trong `resources/js/admin/pages/post/form.js`:

```javascript
function initScheduledAt() {
    const $scheduledAtInput = $("#scheduled_at");
    const $statusSelect = $("#status");
    const $scheduledAtField = $scheduledAtInput.closest(".mb-3");

    if ($scheduledAtInput.length === 0) {
        return;
    }

    const isDisabled = $scheduledAtInput.prop("disabled");

    // Hàm để ẩn/hiện input scheduled_at
    function toggleScheduledAtField(show) {
        if (show) {
            $scheduledAtField.slideDown(300);
        } else {
            $scheduledAtField.slideUp(300);
        }
    }

    // Kiểm tra trạng thái ban đầu và ẩn/hiện field
    const initialStatus = $statusSelect.val();
    if (initialStatus === "scheduled") {
        $scheduledAtField.show();
    } else {
        $scheduledAtField.hide();
    }

    if (!isDisabled && typeof flatpickr !== "undefined") {
        // Khởi tạo Flatpickr cho scheduled_at
        const flatpickrInstance = flatpickr($scheduledAtInput[0], {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            minDate: "today",
            minuteIncrement: 1,
            locale: {
                firstDayOfWeek: 1,
            },
            onChange: function (selectedDates, dateStr, instance) {
                if (dateStr) {
                    // Khi chọn scheduled_at, đổi status thành "scheduled" và hiện field
                    if ($statusSelect.val() !== "scheduled") {
                        $statusSelect.val("scheduled").trigger("change");
                        toastr.info(
                            'Bài viết sẽ được đặt ở trạng thái "Lên lịch" và sẽ tự động đăng vào thời gian đã chọn.',
                            "Thông báo"
                        );
                    }
                    // Đảm bảo field được hiện
                    toggleScheduledAtField(true);
                } else {
                    $scheduledAtInput.val("");
                    // Nếu đang ở status "scheduled" và xóa scheduled_at, đổi về "draft" và ẩn field
                    if ($statusSelect.val() === "scheduled") {
                        $statusSelect.val("draft").trigger("change");
                    }
                }
            },
        });

        // Handle existing value (edit form)
        if ($scheduledAtInput.val()) {
            const currentValue = $scheduledAtInput.val();
            if (currentValue) {
                const formattedValue = currentValue.replace("T", " ");
                flatpickrInstance.setDate(formattedValue, false);
            }
        }

        // Xử lý khi status thay đổi
        $statusSelect.on("change", function () {
            const selectedStatus = $(this).val();

            if (selectedStatus === "scheduled") {
                // Khi chọn "scheduled", hiện input và tự động mở flatpickr
                toggleScheduledAtField(true);

                // Highlight input
                $scheduledAtInput.addClass("border-warning");
                setTimeout(() => {
                    $scheduledAtInput.removeClass("border-warning");
                }, 2000);

                // Tự động mở flatpickr nếu chưa có scheduled_at
                if (flatpickrInstance.selectedDates.length === 0) {
                    setTimeout(() => {
                        flatpickrInstance.open();
                    }, 350);
                    toastr.info(
                        "Vui lòng chọn thời gian đăng bài.",
                        "Thông báo"
                    );
                } else {
                    // Nếu đã có scheduled_at, chỉ focus vào input
                    $scheduledAtInput.focus();
                }
            } else {
                // Khi chọn status khác (published, draft), ẩn input và xóa scheduled_at
                toggleScheduledAtField(false);

                if (selectedStatus === "published") {
                    // Khi chọn "published", phải xóa scheduled_at
                    if (flatpickrInstance.selectedDates.length > 0) {
                        flatpickrInstance.clear();
                        $scheduledAtInput.val("");
                        toastr.info(
                            'Đã xóa lịch đăng bài vì bài viết được đặt ở trạng thái "Xuất bản".',
                            "Thông báo"
                        );
                    }
                } else {
                    // Khi chọn "draft", xóa scheduled_at nếu có
                    if (flatpickrInstance.selectedDates.length > 0) {
                        flatpickrInstance.clear();
                        $scheduledAtInput.val("");
                    }
                }
            }
        });

        $scheduledAtInput.data("flatpickr", flatpickrInstance);
    }
}

$(document).ready(function () {
    initScheduledAt();
});
```

**Lưu ý:**

-   Flatpickr format: `Y-m-d H:i` (ví dụ: "2025-12-12 14:30")
-   `minDate: "today"` - Không cho chọn ngày trong quá khứ
-   `enableTime: true` - Cho phép chọn cả ngày và giờ
-   `time_24hr: true` - Hiển thị giờ 24h
-   Tự động đổi status sang "scheduled" khi chọn scheduled_at
-   Tự động clear scheduled_at khi chọn status published hoặc draft
-   **Input chỉ hiện khi status = "scheduled"**, ẩn khi status khác
-   Tự động mở flatpickr khi chọn status "scheduled" và chưa có scheduled_at

---

## 10. Workflow chi tiết

### 10.1. Workflow: Tạo bài viết với lịch đăng

1. **User tạo bài viết mới:**

    - Điền thông tin bài viết
    - Chọn trạng thái "Lên lịch" → Input `scheduled_at` tự động hiện
    - Flatpickr tự động mở để chọn thời gian
    - Chọn ngày và giờ đăng bài (ví dụ: 12/12/2025 14:30)
    - Status tự động đổi sang "scheduled" (nếu chưa)

2. **Submit form:**

    - Controller nhận `scheduled_at` = "2025-12-12 14:30"
    - Validation: Kiểm tra `scheduled_at > now()`
    - Controller lưu:
        - `status = 'scheduled'` (bắt buộc)
        - `scheduled_at = "2025-12-12 14:30"`

3. **Database:**
    - Record được tạo với `status = 'scheduled'` và `scheduled_at = '2025-12-12 14:30:00'`

### 10.2. Workflow: Đăng bài tự động

1. **Schedule chạy (mỗi phút):**

    - Command `posts:publish-scheduled` được gọi
    - Query: Lấy tất cả bài viết với:
        - `status = 'scheduled'`
        - `scheduled_at IS NOT NULL`
        - `scheduled_at <= NOW()`

2. **Xử lý từng bài viết:**

    - Update `status = 'published'`
    - Update `scheduled_at = NULL`
    - Save vào database

3. **Gửi email:**

    - Load relationship `user`
    - Gửi `PostPublishedNotification` email
    - Nếu email fail, log error nhưng không throw exception

4. **Kết quả:**
    - Bài viết được đăng công khai
    - User nhận email thông báo
    - `scheduled_at` được clear

### 10.3. Workflow: Chỉnh sửa lịch đăng

1. **Trường hợp có thể chỉnh sửa:**

    - Bài viết chưa được đăng (`status = 'draft'` hoặc `status = 'scheduled'`)
    - Thời gian `scheduled_at` chưa đến (`scheduled_at > now()`)
    - User có thể:
        - Thay đổi `scheduled_at`
        - Xóa `scheduled_at` (đặt về null) → Status tự động đổi về "draft"

2. **Trường hợp không thể chỉnh sửa:**
    - Bài viết đã được đăng (`status = 'published'`)
        - Input bị disable và ẩn
        - Hiển thị: "Bài viết đã được đăng"
    - Thời gian `scheduled_at` đã qua (`scheduled_at <= now()`)
        - Input bị disable
        - Hiển thị: "Đã đến thời gian đăng bài. Bài viết sẽ được đăng tự động."

### 10.4. Workflow: Xóa lịch đăng

1. **User xóa scheduled_at:**

    - Xóa giá trị trong Flatpickr (clear)
    - Submit form với `scheduled_at = null` hoặc empty

2. **Controller xử lý:**
    - Nếu `canEditScheduled = true` và `!filled('scheduled_at')`:
        - Set `scheduled_at = null`
        - Nếu `status = 'scheduled'`, đổi về `status = 'draft'`
    - Input tự động ẩn

### 10.5. Workflow: Chuyển đổi giữa các trạng thái

1. **Draft → Scheduled:**

    - User chọn status = "scheduled"
    - Input `scheduled_at` tự động hiện
    - Flatpickr tự động mở
    - User chọn thời gian → `scheduled_at` được lưu

2. **Scheduled → Published:**

    - User chọn status = "published"
    - Input `scheduled_at` tự động ẩn
    - `scheduled_at` tự động bị xóa
    - Bài viết được đăng ngay

3. **Scheduled → Draft:**
    - User xóa `scheduled_at` hoặc chọn status = "draft"
    - Input `scheduled_at` tự động ẩn
    - `scheduled_at` được set về `null`
    - Status đổi về "draft"

---

## 11. Lưu ý quan trọng

### 11.1. Quy tắc nghiệp vụ

1. **Status và Scheduled_at:**

    - Không thể có `status = 'published'` và `scheduled_at` cùng lúc
    - Nếu có `scheduled_at`, **bắt buộc** phải `status = 'scheduled'`
    - Nếu `status = 'scheduled'`, **bắt buộc** phải có `scheduled_at`
    - Nếu `status = 'published'`, **bắt buộc** phải `scheduled_at = null`
    - Nếu `status = 'draft'`, có thể có hoặc không có `scheduled_at`

2. **Chỉnh sửa scheduled_at:**

    - Chỉ cho phép chỉnh sửa khi:
        - Bài viết chưa được đăng (`status !== 'published'`)
        - Thời gian chưa đến (`scheduled_at > now()`)

3. **Validation:**

    - `scheduled_at` phải sau thời gian hiện tại (`after:now`)
    - Format: `Y-m-d H:i` (ví dụ: "2025-12-12 14:30")
    - Enum `status` bao gồm: `'draft'`, `'scheduled'`, `'published'`

4. **UI/UX:**
    - Input `scheduled_at` chỉ hiển thị khi status = "scheduled"
    - Tự động mở flatpickr khi chọn status "scheduled" và chưa có scheduled_at
    - Highlight input khi chọn status "scheduled"

### 11.2. Performance

1. **Command Performance:**

    - Command chạy mỗi phút, nên query phải tối ưu
    - Sử dụng index trên `status` và `scheduled_at`
    - Eager load `user` relationship để tránh N+1

2. **Email Sending:**
    - Email được gửi đồng bộ trong command
    - Nếu cần xử lý nhiều bài viết, nên queue email
    - Không throw exception nếu email fail

### 11.3. Schedule Setup

#### Chạy Command thủ công (1 lần)

Để chạy command một lần và kiểm tra kết quả:

```bash
php artisan posts:publish-scheduled
```

**Lưu ý:**

-   Command sẽ chạy một lần, xử lý tất cả bài viết đã đến thời gian, rồi thoát
-   Hữu ích cho việc testing hoặc chạy thủ công khi cần

#### Chạy Scheduler ở chế độ nền (Development)

Trong môi trường development, sử dụng `schedule:work`:

```bash
php artisan schedule:work
```

**Lưu ý:**

-   Chạy ở chế độ nền, liên tục kiểm tra các scheduled tasks
-   Tự động chạy `posts:publish-scheduled` mỗi phút (theo cấu hình trong `routes/console.php`)
-   Chạy đến khi bạn dừng bằng `Ctrl+C`
-   Chỉ dùng cho development, không dùng trong production

#### Cấu hình Cron Job (Production)

Trong môi trường production, cấu hình cron job:

Thêm vào crontab (`crontab -e`):

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

**Lưu ý:**

-   Cron job chạy `schedule:run` **mỗi phút**
-   `schedule:run` sẽ kiểm tra và chạy các task đã đến thời gian (trong đó có `posts:publish-scheduled`)
-   Khác với `schedule:work`, `schedule:run` chạy một lần rồi thoát, cron sẽ gọi lại mỗi phút
-   Đây là cách chính thức để chạy Laravel scheduler trong production

#### So sánh các cách chạy

| Cách chạy                             | Khi nào dùng           | Cách hoạt động                                                |
| ------------------------------------- | ---------------------- | ------------------------------------------------------------- |
| `php artisan posts:publish-scheduled` | Testing, chạy thủ công | Chạy 1 lần → Xong → Thoát                                     |
| `php artisan schedule:work`           | Development            | Chạy nền → Mỗi phút check → Chạy task → Lặp lại               |
| Cron + `schedule:run`                 | Production             | Cron gọi `schedule:run` mỗi phút → Check và chạy task → Thoát |

### 11.4. Testing

1. **Test Command:**

    ```bash
    php artisan posts:publish-scheduled
    ```

    - Chạy một lần ngay khi gọi
    - Thực thi command xong rồi thoát
    - Dùng để test hoặc chạy thủ công

2. **Test với dữ liệu giả:**

    - Tạo bài viết với `status = 'scheduled'` và `scheduled_at` trong quá khứ
    - Chạy command để verify đăng thành công
    - Kiểm tra email được gửi

3. **Test UI:**
    - Chọn status "scheduled" → Input phải hiện và flatpickr mở
    - Chọn status "published" → Input phải ẩn và scheduled_at bị xóa
    - Chọn status "draft" → Input phải ẩn

---

## 12. Checklist khi triển khai

### Database

-   [x] Migration có enum `status` với giá trị `'scheduled'`
-   [x] Migration có cột `scheduled_at` (timestamp, nullable)

### Model

-   [x] Thêm `scheduled_at` vào `$fillable`
-   [x] Cast `scheduled_at` thành `datetime`
-   [x] Thêm constant `STATUS_SCHEDULED = 'scheduled'`

### Repository

-   [x] Thêm `scheduled_at` vào `gridData()` select
-   [x] Cập nhật `status_html` để hiển thị trạng thái "Lên lịch"
-   [x] Cập nhật trash list `status_html` để hiển thị status "scheduled"

### Command

-   [x] Tạo `PublishScheduledPosts` command
-   [x] Query tìm posts với `status = 'scheduled'` (không phải `'draft'`)
-   [x] Đăng ký command vào `routes/console.php`
-   [x] Test command manual

### Mail

-   [x] Tạo `PostPublishedNotification` Mailable
-   [x] Tạo email template `emails/post-published.blade.php`

### Controller

-   [x] Update `store()` để xử lý `scheduled_at` và set `status = 'scheduled'`
-   [x] Update `update()` với logic kiểm tra `canEditScheduled`
-   [x] Update `edit()` để truyền `$post` và `$canEditScheduledAt`

### Request

-   [x] Thêm validation rule `scheduled_at` vào `StoreRequest`
-   [x] Thêm validation rule `scheduled_at` vào `UpdateRequest`
-   [x] Cập nhật enum `status` để bao gồm `'scheduled'`

### Views

-   [x] Input `scheduled_at` ẩn mặc định trong create form
-   [x] Input `scheduled_at` chỉ hiện khi `$isScheduled = true` trong edit form
-   [x] Include Flatpickr CSS và JS
-   [x] Cập nhật text mô tả

### JavaScript

-   [x] Khởi tạo Flatpickr cho `scheduled_at`
-   [x] Logic ẩn/hiện input dựa trên status
-   [x] Xử lý onChange: Tự động đổi status sang "scheduled"
-   [x] Xử lý status change: Hiện input khi chọn "scheduled", ẩn khi chọn status khác
-   [x] Tự động mở flatpickr khi chọn status "scheduled" và chưa có scheduled_at
-   [x] Clear scheduled_at khi chọn published hoặc draft

---

## 13. Troubleshooting

### Command không chạy

-   Kiểm tra cron job có chạy không: `crontab -l`
-   Kiểm tra log: `storage/logs/laravel.log`
-   Test manual: `php artisan posts:publish-scheduled`

### Email không được gửi

-   Kiểm tra cấu hình mail trong `.env`
-   Kiểm tra `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`
-   Test email: `php artisan tinker` → `Mail::raw('test', function($m) { $m->to('test@example.com')->subject('test'); });`

### Flatpickr không hiển thị

-   Kiểm tra Flatpickr CSS và JS có được load không
-   Kiểm tra console có lỗi JavaScript không
-   Đảm bảo input không bị disable khi khởi tạo Flatpickr

### Scheduled_at không được lưu

-   Kiểm tra validation có pass không
-   Kiểm tra `$fillable` có `scheduled_at` không
-   Kiểm tra format datetime có đúng `Y-m-d H:i` không

### Input không hiện khi chọn status "scheduled"

-   Kiểm tra JavaScript có được load không
-   Kiểm tra console có lỗi JavaScript không
-   Kiểm tra `toggleScheduledAtField` function có được gọi không
-   Kiểm tra CSS `display: none` có bị override không

### Status không tự động đổi khi chọn scheduled_at

-   Kiểm tra event handler `onChange` của flatpickr
-   Kiểm tra `$statusSelect.val("scheduled").trigger("change")` có được gọi không
-   Kiểm tra Select2 có được khởi tạo đúng không

---

## 14. Tóm tắt thay đổi so với phiên bản cũ

### Thay đổi chính:

1. **Status mới: "scheduled"**

    - Trước: Bài viết có `scheduled_at` nhưng `status = 'draft'`
    - Bây giờ: Bài viết có `scheduled_at` và `status = 'scheduled'`

2. **Input chỉ hiện khi cần:**

    - Trước: Input `scheduled_at` luôn hiển thị
    - Bây giờ: Input chỉ hiện khi status = "scheduled"

3. **Logic tự động:**

    - Khi chọn status "scheduled" → Input hiện và flatpickr tự động mở
    - Khi chọn scheduled_at → Status tự động đổi thành "scheduled"
    - Khi xóa scheduled_at → Status tự động đổi về "draft"

4. **Command query:**
    - Trước: Tìm posts với `status = 'draft'` và `scheduled_at <= now()`
    - Bây giờ: Tìm posts với `status = 'scheduled'` và `scheduled_at <= now()`

---

**Tài liệu này mô tả chi tiết workflow và cách triển khai chức năng Đăng bài theo lịch (Scheduled Posts) với status "scheduled" mới.**
