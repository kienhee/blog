@extends('client.pages.profile.layout')

@section('profile-content')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mạng xã hội</h5>
        </div>
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-sm-6">
                    <div class="d-flex align-items-center">
                        <i class="bx bxl-twitter text-info me-2 fs-4"></i>
                        <div>
                            <small class="text-muted d-block">Twitter</small>
                            <span>{{ $user->twitter_url ?? 'Chưa cập nhật' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="d-flex align-items-center">
                        <i class="bx bxl-facebook text-primary me-2 fs-4"></i>
                        <div>
                            <small class="text-muted d-block">Facebook</small>
                            <span>{{ $user->facebook_url ?? 'Chưa cập nhật' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="d-flex align-items-center">
                        <i class="bx bxl-instagram text-danger me-2 fs-4"></i>
                        <div>
                            <small class="text-muted d-block">Instagram</small>
                            <span>{{ $user->instagram_url ?? 'Chưa cập nhật' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="d-flex align-items-center">
                        <i class="bx bxl-linkedin text-primary me-2 fs-4"></i>
                        <div>
                            <small class="text-muted d-block">LinkedIn</small>
                            <span>{{ $user->linkedin_url ?? 'Chưa cập nhật' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
