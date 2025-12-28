@extends('client.layouts.master')
@section('title', '#' . ($hashtag->name ?? 'Tag'))

@section('content')
    <section class="section-py">
        <div class="main-container">
            {{-- Hashtag Header --}}
            <header class="category-header mb-5">
                <h1 class="category-title mb-3">#{{ $hashtag->name }}</h1>
            </header>

            {{-- Posts Grid --}}
            @include('client.components.post.posts-grid', ['posts' => $posts])
        </div>
    </section>
@endsection

