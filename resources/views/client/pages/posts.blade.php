@extends('client.layouts.master')

@section('title', 'Danh sách bài viết')

@section('content')
    <!-- Blog List: Start -->
    <section class="section-py custom-pagination">
        <div class="container">
            <header class="category-header mb-5">
                <h1 class="category-title mb-3">Danh sách bài viết</h1>
                <hr>  
            </header>

            <!-- Posts Grid -->
            @include('client.components.post.posts-grid', ['posts' => $posts])
        </div>
    </section>
    <!-- Blog List: End -->
@endsection
