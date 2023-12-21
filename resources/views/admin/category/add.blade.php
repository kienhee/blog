@extends('layouts.admin.index')
@section('title', 'Thêm mới danh mục')

@section('content')
<x-breadcrumb parentName="Danh mục" parentLink="dashboard.category.index" childrenName="Thêm mới danh mục" />
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <x-alert />
            <x-header-table tableName="Thêm mới danh mục" link="dashboard.category.index" linkName="Tất cả danh mục" />
            <!-- Tài khoản -->
            <div class="card-body">
                <form id="formAccountSettings" action="{{ route('dashboard.category.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="name" class="form-label">Tên danh mục : <span
                                    class="text-danger">*</span></label>
                            <input class="form-control @error('name') is-invalid @enderror " type="text"
                                oninput="createSlug('name','slug')" id="name" name="name" value="{{ old('name') }}"
                                placeholder="Tên danh mục" autofocus />
                            @error('name')
                            <p class="text-danger my-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="slug" class="form-label">URL: <span class="text-danger">*</span></label>
                            <input class="form-control @error('slug') is-invalid @enderror" type="text" id="slug"
                                name="slug" value="{{ old('slug') }}" placeholder="URL" />
                            @error('slug')
                            <p class="text-danger my-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Loại</label>
                            <select id="type" class="form-select" name="type">
                                <option value="1" {{ old('type') == "1" ? "selected" : "" }}>Bài viết</option>
                                <option value="2" {{ old('type') == "2" ? "selected" : "" }}>Dự án</option>
                            </select>
                        </div>

                        <div class=" mb-3 col-md-12">
                            <label for="description" class="form-label">Mô tả:</label>

                            <textarea class="form-control @error('description') is-invalid @enderror " id="description"
                                rows="5" name="description"
                                placeholder="Tối đa 255 ký tự">{{ old('description') }}</textarea>
                            @error('description')
                            <p class="text-danger my-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary me-2">Thêm mới danh mục</button>
                        <button type="reset" class="btn btn-outline-secondary">Đặt lại</button>
                    </div>
                </form>
            </div>
            <!-- /Tài khoản -->
        </div>
    </div>
</div>
@endsection
