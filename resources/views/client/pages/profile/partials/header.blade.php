@php
    $user = Auth::user();
    $avatar = $user->avatar ?: asset_shared_url('images/default.png');
    $displayName = $user->full_name ?: $user->email;
    
    // Xác định tab active dựa trên route hiện tại
    $currentRoute = Route::currentRouteName();
    $activeTab = 'profile';
    if ($currentRoute === 'client.profile.savedPosts') {
        $activeTab = 'saved-posts';
    } elseif ($currentRoute === 'client.profile.changePassword') {
        $activeTab = 'password';
    }
@endphp

<!-- Header -->
<section class="section-py pt-0">
    <div class="card mb-4">
        <div class="user-profile-header-banner">
            <img id="profileBannerImg" src="{{ asset_admin_url('assets/img/pages/profile-banner.png') }}" alt="Banner image" class="rounded-top" />
        </div>
        <div class="user-profile-header container d-flex flex-column flex-sm-row text-sm-start text-center mb-4">
            <div class="flex-shrink-0 mt-n2 mx-sm-0 mx-auto">
                <img id="profileAvatarImg" src="{{ $avatar }}" alt="user image"
                    class="d-block h-auto ms-0 ms-sm-4 rounded user-profile-img" />
            </div>
            <div class="flex-grow-1 mt-3 mt-sm-5">
                <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-4 flex-md-row flex-column gap-4">
                    <div class="user-profile-info text-start">
                        <h4 class="mb-1" id="profileDisplayName">{{ $displayName }}</h4>
                        <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-2">
                            <li class="list-inline-item fw-medium">
                                <i class="bx bx-envelope"></i>
                                <span id="profileEmail">{{ $user->email }}</span>
                            </li>
                            @if ($user->phone)
                                <li class="list-inline-item fw-medium">
                                    <i class="bx bx-phone"></i>
                                    <span id="profilePhone">{{ $user->phone }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <div id="success-notice" class="alert alert-success alert-dismissible fade show d-none" role="alert">
            <span id="success-notice-message"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Navigation Tabs -->
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-sm-row" role="tablist" id="profile-tabs">
                    <li class="nav-item">
                        <a href="{{ route('client.profile.index') }}" 
                           class="nav-link {{ $activeTab === 'profile' ? 'active' : '' }}">
                            <i class="bx bx-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('client.profile.savedPosts') }}" 
                           class="nav-link {{ $activeTab === 'saved-posts' ? 'active' : '' }}">
                            <i class="bx bx-bookmark me-1"></i> Bài viết đã lưu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('client.profile.changePassword') }}" 
                           class="nav-link {{ $activeTab === 'password' ? 'active' : '' }}">
                            <i class="bx bx-lock-alt me-1"></i> Đổi mật khẩu
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <!--/ Navigation Tabs -->

        <!-- Content -->
        <div class="mt-4">
            {!! $profileContent ?? '' !!}
        </div>
        <!--/ Content -->
    </div>
</section>

