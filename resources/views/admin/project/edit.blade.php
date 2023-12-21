@extends('layouts.admin.index')
@section('title', 'Cập Nhật Dự Án')
@section('content')

    <x-breadcrumb parentName="Dự Án" parentLink="dashboard.project.index" childrenName="Cập Nhật Dự Án" />
    <form action="{{ route('dashboard.project.update', $project->id) }}" method="POST" class="col-xl"
        enctype="multipart/form-data">
        @csrf
        @method('put')
        <div class="card mb-4">
            <x-alert />
            <x-header-table tableName="Cập Nhật Dự Án" link="dashboard.project.index" linkName="Tất Cả Dự Án" />
            <div class="card-body">

                <div class="d-flex flex-column align-items-center justify-content-center gap-4 mb-3">
                    <img src="{{ $project->cover ?? asset('images/image-preview.png') }}" alt="Ảnh Bìa"
                        class=" rounded cover-img-project img-fluid " id="uploadedAvatar" />
                    <div class="button-wrapper text-center">
                        <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                            <span class="d-none d-sm-block">Tải Ảnh Bìa</span>
                            <i class="bx bx-upload d-block d-sm-none"></i>
                            <input type="file" id="upload" class="account-file-input" hidden name="cover"
                                accept="image/png, image/jpeg" />
                            <input type="hidden" value="{{ $project->cover }}" name="cover">
                        </label>
                    </div>
                    @error('cover')
                        <p class="text-danger my-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="row mt-3">


                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="title">Tiêu Đề:<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ old('title') ?? $project->title }}" oninput="createSlug('title','url-slug')" />
                        @error('title')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="url-slug">URL:<span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" id="url-slug" name="slug"
                                value="{{ old('slug') ?? $project->slug }}" />
                        </div>
                        @error('slug')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="website">Website:</label>
                        <input type="text" class="form-control" id="website" name="website"
                            value="{{ old('website') ?? $project->website }}" />
                        @error('website')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="categories_select" class="form-label">Danh Mục:<span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="categories_select" name="category_project_id">
                            <option value="">Chọn một danh mục</option>
                            @foreach (getAllCategories(2) as $item)
                                <option value="{{ $item->id }}"
                                    {{ old('category_project_id') == $item->id || $project->category_project_id == $item->id ? 'selected' : false }}>
                                    {{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('category_project_id')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Mô Tả:<span class="text-danger">*</span></label>
                        <textarea id="description" class="form-control" name="description">{{ old('description') ?? $project->description }}</textarea>
                        @error('description')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>



                    <div class="mb-3">
                        <label class="form-label" for="content">Nội Dung:<span class="text-danger">*</span></label>
                        <textarea id="content" class="form-control" name="content">{{ old('content') ?? $project->content }}</textarea>
                        @error('content')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>



                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-outline-secondary">Làm Mới</button>
                    <button type="submit" class="btn btn-outline-primary">Cập Nhật Bài Viết</button>
                </div>

            </div>
        </div>
    </form>
    <script>
        let imgInp = document.getElementById('upload');
        let preview = document.getElementById('uploadedAvatar');
        imgInp.onchange = evt => {
            const [file] = imgInp.files
            if (file) {
                preview.src = URL.createObjectURL(file)
                preview.classList.remove("d-none")
                preview.classList.add("d-block")
            }
        }
    </script>

@endsection
