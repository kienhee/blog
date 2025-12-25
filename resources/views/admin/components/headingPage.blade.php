{{--
    @var string $heading
    @var string|null $description
    @var string|null $button
    @var string|null $buttonLink
    @var string|null $listLink
--}}
<div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
    <div>
        <h5 class="mb-1 mt-3">@yield('title')</h5>
        @if (!empty($description))
            <small class="text-muted">{{ $description }}</small>
        @endif
    </div>

    <div class="d-flex gap-2">
        @switch($button)
            @case('add')
                <a href="{{ route($buttonLink) }}" class="btn btn-primary">
                    <i class='bx bx-plus-circle me-1'></i> Thêm mới
                </a>
            @break

            @case('create')
                <div class="d-flex gap-2">
                    @if ($listLink)
                        <a href="{{ route($listLink) }}" class="btn btn-label-secondary">
                            <i class='bx bx-arrow-back'></i> Quay lại
                        </a>
                    @endif
                    <button type="submit" id="submit_btn" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <i class='bx bx-plus-circle me-1'></i> Thêm mới
                    </button>
                </div>
            @break

            @case('edit')
                <div class="d-flex gap-2">
                    @if ($listLink)
                        <a href="{{ route($listLink) }}" class="btn btn-label-secondary">
                            <i class='bx bx-arrow-back'></i> Quay lại
                        </a>
                    @endif
                    @if (isset($previewLink) && $previewLink)
                        <a href="{{ route($previewLink, $previewId ?? null) }}" target="_blank" class="btn btn-label-info">
                            <i class='bx bx-show'></i> Xem trước
                        </a>
                    @endif
                    <button type="submit" id="submit_btn" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <i class='bx bx-save me-1'></i> Cập nhật
                    </button>
                </div>
            @break

            @default
                {{-- Có thể thêm mặc định nếu cần --}}
        @endswitch
    </div>
</div>

@include('admin.components.showMessage')
