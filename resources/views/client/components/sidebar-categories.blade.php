{{-- Categories Widget --}}
@if (isset($allCategories) && $allCategories->count() > 0)
    <div class="sidebar-widget ">
        <h3 class="sidebar-widget-title mb-3">
            <i class="bx bx-folder"></i>
            Danh mục
        </h3>
        <ul class="category-list">
            @foreach ($allCategories->take(10) as $category)
                <li class="category-item">
                    <a href="{{ route('client.category', ['slug' => $category->slug ?? '']) }}"
                        class="category-link">
                        <span>{{ $category->name }}</span>
                        @if (isset($category->post_count))
                            <span class="category-count">{{ $category->post_count }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif

