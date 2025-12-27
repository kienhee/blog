@extends('client.layouts.master')
@section('title', 'Bài viết đã lưu')

@push('styles')
    <link rel="stylesheet" href="{{ asset_admin_url('assets/vendor/css/pages/page-profile.css') }}" />
@endpush

@section('content')
    @php
        ob_start();
    @endphp
    <!-- Saved Posts Content -->
                @if(isset($savedPosts) && $savedPosts->count() > 0)
                    @php
                        // Map saved posts to posts collection
                        $posts = $savedPosts->map(function($savedPost) {
                            return $savedPost->post;
                        })->filter()->values();
                    @endphp
                    <div class="row g-5 mb-5">
                        @foreach ($posts as $post)
                            @if($post)
                                <div class="col-md-6 col-lg-4">
                                    @include('client.components.post-card', [
                                        'post' => $post,
                                        'showButton' => true,
                                        'buttonText' => 'Đọc thêm',
                                        'buttonClass' => 'text-primary',
                                        'descriptionLimit' => 120,
                                    ])
                                </div>
                            @endif
                        @endforeach
                    </div>
                    {{-- Pagination --}}
                    <div class="d-flex justify-content-center">
                        {{ $savedPosts->links('vendor.pagination.bootstrap-5-custom') }}
                    </div>
                @else
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bx bx-bookmark" style="font-size: 48px; color: #ccc;"></i>
                            <p class="text-muted mt-3">Bạn chưa lưu bài viết nào.</p>
                        </div>
                    </div>
                @endif
    <!--/ Saved Posts Content -->
    @php
        $profileContent = ob_get_clean();
    @endphp
    @include('client.pages.profile.partials.header', ['profileContent' => $profileContent])
@endsection

