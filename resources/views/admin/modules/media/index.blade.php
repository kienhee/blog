@extends('admin.layouts.master')
@section("title", "Media")
@push('styles')
<style>
    .media {
        width: 100%;
        height: calc(100vh - 200px);
        overflow: hidden;
        border: none;
        border-radius:8px;
    }
</style>
@endpush
@section("content")
<iframe src="/filemanager" class="media"></iframe>
@endsection
