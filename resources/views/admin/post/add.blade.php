@extends('layouts.admin.index')
@section('title', 'Thêm Bài Viết Mới')
@section('content')

    <x-breadcrumb parentName="Bài Viết" parentLink="dashboard.post.index" childrenName="Thêm Bài Viết Mới" />
    <form action="{{ route('dashboard.post.add') }}" method="POST" class="col-xl" enctype="multipart/form-data">
        @csrf
        <div class="card mb-4">
            <x-alert />
            <x-header-table tableName="Thêm Bài Viết Mới" link="dashboard.post.index" linkName="Tất Cả Bài Viết" />
            <div class="card-body">

                <div class="d-flex flex-column align-items-center justify-content-center gap-4 mb-3">
                    <img src="{{ asset('images/image-preview.png') }}" alt="Cover"
                        class="d-none rounded cover-img-post img-fluid " id="uploadedAvatar" />
                    <div class="button-wrapper text-center">
                        <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                            <span class="d-none d-sm-block">Tải Ảnh Bìa</span>
                            <i class="bx bx-upload d-block d-sm-none"></i>
                            <input type="file" id="upload" class="account-file-input" hidden name="cover"
                                accept="image/png, image/jpeg" />
                        </label>
                    </div>
  @error('cover')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                </div>
                <div class="row mt-3">


                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="title">Tiêu Đề:<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}"
                            oninput="createSlug('title','url-slug')" />
                        @error('title')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="url-slug">URL:<span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" id="url-slug" name="slug"
                                value="{{ old('slug') }}" />
                        </div>
                        @error('slug')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="categories_select" class="form-label">Danh Mục:<span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="categories_select" name="category_id">
                            <option value="">Chọn một danh mục</option>
                            @foreach (getAllCategories() as $item)
                                <option value="{{ $item->id }}"
                                    {{ old('category_id') == $item->id ? 'selected' : false }}>
                                    {{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="select-multiple" class="form-label">#Hashtags:<span class="text-danger">*</span></label>
                        <select id="select-multiple" multiple name="tags" placeholder="Tags" data-search="true"
                            data-silent-initial-value-set="true">
                            @foreach (getAllTags() as $tag)
                                <option value="{{ $tag->name }}">{{ $tag->name }}
                                </option>
                            @endforeach

                        </select>
                        @error('tags')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Mô Tả:<span class="text-danger">*</span></label>
                        <textarea id="description" class="form-control" name="description" placeholder="Khoảng 255 ký tự">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>



                    <div class="mb-3">
                        <label class="form-label" for="content">Nội Dung:<span class="text-danger">*</span></label>
                        <textarea id="content" class="form-control" name="content">{{ old('content') }}</textarea>
                        @error('content')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="title_meta">Tiêu Đề (SEO):<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title_meta" name="title_meta"
                            placeholder="Tối đa 38 ký tự" value="{{ old('title_meta') }}" />
                        @error('title_meta')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="description_meta">Mô Tả (SEO):<span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="description_meta" name="description_meta"
                            placeholder="Tối đa 139 ký tự" value="{{ old('description_meta') }}" />
                        @error('description_meta')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-outline-secondary">Làm Mới</button>
                    <button type="submit" class="btn btn-outline-primary">Tạo Bài Viết</button>
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
