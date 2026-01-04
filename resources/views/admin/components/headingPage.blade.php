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
                @if (empty($buttonPermission) || auth()->user()->can($buttonPermission))
                    <a href="{{ route($buttonLink) }}" class="btn btn-primary">
                        <i class='bx bx-plus-circle me-1'></i> Thêm mới
                    </a>
                @endif
            @break

            @case('create')
                <div class="d-flex gap-2">
                    @if ($listLink)
                        <a href="{{ route($listLink) }}" class="btn btn-label-secondary">
                            <i class='bx bx-arrow-back'></i> Quay lại
                        </a>
                    @endif
                    @if (empty($buttonPermission) || auth()->user()->can($buttonPermission))
                        <button type="submit" id="submit_btn" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                            <i class='bx bx-plus-circle me-1'></i> Thêm mới
                        </button>
                    @endif
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
                    @if (empty($buttonPermission) || auth()->user()->can($buttonPermission))
                        <button type="submit" id="submit_btn" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                            <i class='bx bx-save me-1'></i> Cập nhật
                        </button>
                    @endif
                </div>
            @break

            @case('list')
                <div class="d-flex gap-2">
                    @if ($listLink)
                        <a href="{{ route($listLink) }}" class="btn btn-label-secondary">
                            <i class='bx bx-arrow-back'></i> Quay lại
                        </a>
                    @endif
                </div>
            @break

            @case('back')
                @if (!empty($buttonLink))
                    <a href="{{ $buttonLink }}" class="btn btn-label-secondary">
                        <i class='bx bx-arrow-back'></i> Quay lại
                    </a>
                @endif
            @break

            @case('offcanvas')
                @if (!empty($buttonId))
                    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#{{ $buttonId }}">
                        <i class='bx bx-plus-circle me-1'></i> {{ $buttonText ?? 'Thêm mới' }}
                    </button>
                @endif
            @break

            @default
                {{-- Có thể thêm mặc định nếu cần --}}
        @endswitch
        
        @if(!empty($extraButtons ?? []))
            @foreach($extraButtons as $extraButton)
                @if(!empty($extraButton['type']) && $extraButton['type'] === 'button')
                    <button type="button" 
                            class="btn {{ $extraButton['class'] ?? 'btn-primary' }}"
                            @if(!empty($extraButton['id'])) id="{{ $extraButton['id'] }}" @endif>
                        @if(!empty($extraButton['icon']))
                            <i class="bx {{ $extraButton['icon'] }} me-1"></i>
                        @endif
                        {{ $extraButton['text'] }}
                    </button>
                @else
                    <a href="{{ $extraButton['url'] ?? '#' }}" class="btn {{ $extraButton['class'] ?? 'btn-primary' }}">
                        @if(!empty($extraButton['icon']))
                            <i class="bx {{ $extraButton['icon'] }} me-1"></i>
                        @endif
                        {{ $extraButton['text'] }}
                    </a>
                @endif
            @endforeach
        @endif
    </div>
</div>

@include('admin.components.showMessage')
