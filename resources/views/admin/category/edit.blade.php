@extends('layouts.admin.index')
@section('title', 'Cập Nhật Danh Mục')
@section('content')
    <x-breadcrumb parentName="Danh mục" parentLink="dashboard.category.index" childrenName="Cập nhật danh mục" />
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <x-alert />
                <x-header-table tableName="Cập Nhật Danh Mục" link="dashboard.category.index" linkName="Tất cả danh mục" />
                <div class="card-body">
                    <form id="formAccountSettings" action="{{ route('dashboard.category.update', $category->id) }}"
                        method="POST">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="name" class="form-label">Tên Danh Mục: <span
                                        class="text-danger">*</span></label>
                                <input class="form-control @error('name') is-invalid @enderror " type="text"
                                    oninput="createSlug('name','slug')" id="name" name="name"
                                    value="{{ $category->name ?? old('name') }}" placeholder="Tên Danh Mục" autofocus />
                                @error('name')
                                <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="slug" class="form-label">URL: <span class="text-danger">*</span></label>
                                <input class="form-control @error('slug') is-invalid @enderror" type="text" id="slug"
                                    name="slug" value="{{ $category->slug ?? old('slug') }}" placeholder="URL" />
                                @error('slug')
                                <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="type" class="form-label">Loại</label>
                                <select id="type" class="form-select" name="type">
                                    <option value="1" {{ old('type') == "1" || $category->type == "1" ? "selected" : "" }}>
                                        Bài viết
                                    </option>
                                    <option value="2" {{ old('type') == "2" || $category->type == "2" ? "selected" : "" }}>
                                        Dự án
                                    </option>
                                </select>
                            </div>
                            <div class=" mb-3 col-md-12">
                                <label for="description" class="form-label">Mô Tả:</label>
                                <textarea class="form-control @error('description') is-invalid @enderror " id="description"
                                    rows="5" name="description"
                                    placeholder="Khoảng 255 ký tự">{{ $category->description ?? old('description') }}</textarea>
                                @error('description')
                                <p class="text-danger my-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2">Cập Nhật Danh Mục</button>
                            <button type="reset" class="btn btn-outline-secondary">Đặt lại</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
