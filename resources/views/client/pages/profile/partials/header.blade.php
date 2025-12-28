<div class="card mb-4">
    <div class="user-profile-header-banner">
        <img src="{{ asset_admin_url('assets/img/pages/profile-banner.png') }}" alt="Banner image"
            class="rounded-top"  id="profileBannerImg"/>
    </div>
    <div class="container user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-4">
        @php
            $avatar = $user->avatar ? thumb_path($user->avatar): asset_shared_url('images/default.png');
            $displayName = $user->full_name ?: $user->email;
        @endphp
        <div class="flex-shrink-0 mt-n2 mx-sm-0 mx-auto">
            <img id="profileAvatarImg" src="{{ $avatar }}" alt="user image"
                class="d-block ms-0 ms-sm-4 rounded user-profile-img" />
        </div>
        <div class="flex-grow-1 mt-3 mt-sm-5">
            <div
                class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-4 flex-md-row flex-column gap-4">
                <div class="user-profile-info text-start">
                    <h4 class="mb-1" id="profileDisplayName">{{ $displayName }}</h4>
                    <ul
                        class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-2">
                        <li class="list-inline-item fw-medium"><i class="bx bx-envelope"></i> <span
                                id="profileEmail">{{ $user->email }}</span></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>