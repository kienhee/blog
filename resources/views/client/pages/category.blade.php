@extends('client.layouts.master')

@section('title', $category->name ?? 'Danh mục')

{{-- Breadcrumbs Schema --}}
@include('client.components.seo.breadcrumbs-schema')

@section('content')
    <section class="section-py">
        <div class="container">
            {{-- Category Header --}}
            <header class="category-header mb-5">
                <h1 class="category-title mb-3">{{ $category->name }}</h1>
                @if ($category->description)
                    <p class="category-description text-muted lead">
                        {{ $category->description }}
                    </p>
                @endif
                <hr>  
            </header>

            {{-- Posts Grid --}}
            @include('client.components.post.posts-grid', ['posts' => $posts])
        </div>
    </section>
@endsection
