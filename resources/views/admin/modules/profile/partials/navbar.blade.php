@php
    $navItems = [
        [
            'label' => 'Thông tin cá nhân',
            'icon' => 'bx bx-user',
            'route' => route('admin.users.information'),
        ],
        [
            'label' => 'Đổi mật khẩu',
            'icon' => 'bx bx-lock-alt',
            'route' => route('admin.users.changePassword'),
        ],
    ];
@endphp
<div class="nav-align-top mb-3">
    <ul class="nav nav-pills flex-column flex-md-row mb-6 flex-wrap row-gap-2">
        @foreach ($navItems as $item)
            <li class="nav-item">
                <a class="nav-link {{ $item['route'] == url()->current() ? 'active' : '' }}"
                    href="{{ $item['route'] }}"><i
                        class="icon-base {{ $item['icon'] }} icon-sm me-1_5"></i>{{ $item['label'] }}</a>
            </li>
        @endforeach
    </ul>
</div>
