@extends('layouts.admin.index')
@section('title', 'Cập Nhật Bài Viết')
@section('content')

    <x-breadcrumb parentName="Bài Viết" parentLink="dashboard.post.index" childrenName="Cập Nhật" />
    <form action="{{ route('dashboard.post.update', $post->id) }}" method="POST" enctype="multipart/form-data">
        @method('put')

        @csrf
        <div class="card mb-4">
            <x-alert />
            <x-header-table tableName="Cập Nhật Bài Viết" link="dashboard.post.index" linkName="Tất Cả Bài Viết" />
            <div class="card-body">


                <div class="d-flex flex-column align-items-center justify-content-center gap-4 mb-3">
                    <img src="{{ $post->cover ?? asset('images/image-preview.png') }}" alt="Cover"
                        class="rounded cover-img-post img-fluid " id="uploadedAvatar" />
                    <div class="button-wrapper text-center">
                        <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                            <span class="d-none d-sm-block">Tải Ảnh Bìa</span>
                            <i class="bx bx-upload d-block d-sm-none"></i>
                            <input type="file" id="upload" class="account-file-input" hidden name="cover"
                                accept="image/png, image/jpeg" />
                            <input type="hidden" name="cover" value="{{ $post->cover }}">
                        </label>
                    </div>
                    @error('cover')
                        <p class="text-danger my-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="title">Tiêu Đề:<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ old('title') ?? $post->title }}" oninput="createSlug('title','url-slug')" />
                        @error('title')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="url-slug">URL:<span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" id="url-slug" name="slug"
                                value="{{ old('slug') ?? $post->slug }}" />
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
                                    {{ old('category_id') == $item->id || $post->category_id == $item->id ? 'selected' : false }}>
                                    {{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="select-multiple" class="form-label">#Hashtags:<span class="text-danger">*</span></label>
                        <select id="select-multiple" multiple name="tags" placeholder="Chọn thẻ" data-search="true"
                            data-silent-initial-value-set="true">
                            @foreach (getAllTags() as $tag)
                                <option value="{{ $tag->name }}"
                                    {{ strpos($post->tags, $tag->name) !== false ? 'selected' : ' ' }}>
                                    {{ $tag->name }}
                                </option>
                            @endforeach

                        </select>
                        @error('tags')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Mô Tả:<span class="text-danger">*</span></label>
                        <textarea id="description" class="form-control" name="description" placeholder="Khoảng 500 ký tự">{{ old('description') ?? $post->description }}</textarea>
                        @error('description')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>


                    <div class="mb-3">
                        <label class="form-label" for="content">Nội Dung:<span class="text-danger">*</span></label>
                        <textarea id="content" class="form-control" name="content" placeholder="Nội dung bài viết">{{ old('content') ?? $post->content }}</textarea>
                        @error('content')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="views">Lượt Xem: ({{ $post->views }})</label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" id="views" name="views"
                                value="{{ old('views') }}" />
                        </div>

                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="created_at">Ngày Tạo:</label>
                        <div class="d-flex gap-2">
                            <input type="date" class="form-control" id="created_at" name="created_at"
                                value="{{ old('created_at') ?? $post->created_at->format('Y-m-d') }}" />

                        </div>
                        @error('created_at')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="title_meta">Tiêu Đề (SEO):<span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title_meta" name="title_meta"
                            placeholder="Tối đa 38 ký tự" value="{{ old('title_meta') ?? $post->title_meta }}" />
                        @error('title_meta')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="description_meta">Mô Tả (SEO):<span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="description_meta" name="description_meta"
                            placeholder="Tối đa 139 ký tự"
                            value="{{ old('description_meta') ?? $post->description_meta }}" />
                        @error('description_meta')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="d-flex gap-4">
                        <div>
                            <label for="isComment" class="form-label">Cho Phép Bình Luận: </label>
                            <input class="form-check-input " id="isComment" type="checkbox" name="isComment"
                                {{ $post->isComment ? 'checked' : false }}>
                        </div>

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

            }
        }
    </script>
@endsection
