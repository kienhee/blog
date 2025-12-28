@extends('client.layouts.master')
@section('title', 'Danh sách bài viết')
@push('styles')
    <style>
       
    </style>
@endpush
@section('content')
    <!-- Blog List: Start -->
    <section class="section-py custom-pagination">
        <div class="main-container">
            <header class="category-header mb-5">
                <h1 class="category-title mb-3">Danh sách bài viết</h1>
            </header>
            <!-- Posts Grid -->
            @include('client.components.post.posts-grid', ['posts' => $posts])
        </div>
    </section>
    <!-- Blog List: End -->
@endsection
