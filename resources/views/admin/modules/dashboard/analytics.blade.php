@extends('admin.layouts.master')
@section("title", "Dashboard")
@section("content")
    <div class="row">
        <div class="col-lg-12 mb-4 order-0">
            <div class="card">
                <div class="d-flex align-items-end row">
                    <div class="col-sm-7">
                        <div class="card-body">
                            <h5 class="card-title text-primary">Chào ngày mới {{Auth::user()->full_name}}! 🎉</h5>
                            <p class="mb-4">
                                Hãy bắt đầu một ngày mới tràn đầy năng lượng và hoàn thành mục tiêu của bạn!
                            </p>
                        </div>
                    </div>
                    <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                            <img src="{{ asset_admin_url('assets/img/illustrations/man-with-laptop-light.png') }}" height="140"
                                alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png"
                                data-app-light-img="illustrations/man-with-laptop-light.png" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset_admin_url('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
@endpush
