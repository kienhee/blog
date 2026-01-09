@extends('client.layouts.master')

@section('title', $post->title ?? 'Bài viết')

{{-- SEO Structured Data --}}
@include('client.components.seo.breadcrumbs-schema')
@include('client.components.seo.article-schema')

@push('styles')
    <!-- Unified CSS for Article Content -->
    <link rel="stylesheet" href="{{ asset_shared_url('css/article-content.css') }}">
    <!-- Highlight.js Theme CSS - Defer loading -->
    <link rel="preload" href="{{ asset_shared_url('vendor/highlight/styles/atom-one-dark.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'" />
    <noscript><link rel="stylesheet" href="{{ asset_shared_url('vendor/highlight/styles/atom-one-dark.min.css') }}" /></noscript>
@endpush

@section('content')
    <section class="section-py">
        <div class="main-container">
            {{-- Hero Section --}}
            @include('client.components.post.post-hero')

            {{-- Floating Share Buttons --}}
            @include('client.components.post.floating-share')

            {{-- Main Layout: Content + Sidebar --}}
            <div class="post-layout">
                {{-- Main Content (70%) --}}
                <main class="post-main">
                    {{-- Thumbnail --}}
                    @include('client.components.post.post-thumbnail')

                    {{-- Article Content --}}
                    @include('client.components.post.post-content')

                    {{-- Tags --}}
                    @include('client.components.post.post-tags')

                    {{-- Author Section --}}
                    @include('client.components.post.post-author')

                    {{-- Previous and Next Posts --}}
                    @include('client.components.post.post-prev-next')

                    {{-- Related Posts --}}
                    @include('client.components.post.related-posts')

                    {{-- Comments Section --}}
                    @include('client.components.post.post-comments')
                </main>

                {{-- Sidebar (30%) --}}
                <aside class="post-sidebar">
                    {{-- Table of Contents --}}
                    @include('client.components.sidebar.sidebar-toc')

                    {{-- Categories Widget --}}
                    @include('client.components.sidebar.sidebar-categories')

                    {{-- Hashtags Widget --}}
                    @include('client.components.sidebar.sidebar-hashtags')

                    {{-- Newsletter Subscription Widget --}}
                    @include('client.components.sidebar.sidebar-newsletter')
                </aside>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset_shared_url('vendor/highlight/highlight.min.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/popular.js') }}"></script>
    <script src="{{ asset_admin_url('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
    @vite(['resources/js/client/post/index.js', 'resources/js/client/pages/newsletter.js'])
@endpush
