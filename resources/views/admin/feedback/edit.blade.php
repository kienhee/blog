@extends('layouts.admin.index')
@section('title', 'Cập Nhật Chứng Thực')
@section('content')

    <x-breadcrumb parentName="Chứng Thực" parentLink="dashboard.feedback.index" childrenName="Cập Nhật Chứng Thực" />
    <form action="{{ route('dashboard.feedback.update', $feedback->id) }}" method="POST" class="col-xl"
        enctype="multipart/form-data">
        @csrf
        @method('put')
        <div class="card mb-4">
            <x-alert />
            <x-header-table tableName="Cập Nhật Chứng Thực" link="dashboard.feedback.index" linkName="Tất Cả Chứng Thực" />
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-center flex-column gap-4">
                    <img src="{{ $feedback->avatar ?? asset('images/upload.png') }}" alt="user-avatar"
                        class="d-block rounded-circle " style="object-fit: cover" height="120" width="120"
                        id="uploadedAvatar" />
                    <div class="button-wrapper">
                        <label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
                            <span class="d-none d-sm-block">Tải ảnh đại diện lên</span>
                            <i class="bx bx-upload d-block d-sm-none"></i>
                            <input type="file" id="upload" class="account-file-input" hidden name="avatar"
                                accept="image/png, image/jpeg" />
                        </label>
                    </div>
                    @error('avatar')
                        <p class="text-danger my-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="row mt-3">


                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="name">Tên:<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ old('name') ?? $feedback->name }}" />
                        @error('name')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="career">Nghề Nghiệp:<span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" id="career" name="career"
                                value="{{ old('career') ?? $feedback->career }}" />
                        </div>
                        @error('career')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="feedback">Chứng Thực:<span class="text-danger">*</span></label>
                        <textarea id="feedback" class="form-control" name="feedback" placeholder="Tối đa 255 ký tự">{{ old('feedback') ?? $feedback->feedback }}</textarea>
                        @error('feedback')
                            <p class="text-danger my-1">{{ $message }}</p>
                        @enderror
                    </div>





                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-outline-secondary">Làm mới</button>
                    <button type="submit" class="btn btn-outline-primary">Cập Nhật Chứng Thực</button>
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
