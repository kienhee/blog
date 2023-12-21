@extends('layouts.admin.index')
@section('title', 'Thêm thẻ mới')

@section('content')
    <x-breadcrumb parentName="Thẻ bài viết" parentLink="dashboard.tag.index" childrenName="Thêm thẻ mới" />
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <x-alert />
                <x-header-table tableName="Thêm thẻ mới" link="dashboard.tag.index" linkName="Tất cả thẻ bài viết " />
                <div class="card-body">
                    <form action="{{ route('dashboard.tag.store') }}" method="POST">
                        @csrf
                        <div class="row ">
                            <div class="mb-3 col-md-12">
                                <label for="name" class="form-label">Tên:</label>
                                <input class="form-control @error('name') is-invalid @enderror " type="text"
                                    id="name" name="name" value="{{ old('name') }}" autofocus />
                                @error('name')
                                    <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2">Thêm thẻ mới</button>
                            <button type="reset" class="btn btn-outline-secondary">Đặt lại</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
