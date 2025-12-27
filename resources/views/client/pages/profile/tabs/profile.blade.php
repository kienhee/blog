<div>
    <div class="row">
        <div class="col-lg-4 col-md-5">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <small class="text-muted text-uppercase">Thông tin cá nhân</small>
                        <span class="text-nowrap text-primary cursor-pointer" data-bs-toggle="modal"
                            data-bs-target="#editProfileModal">
                            <i class="bx bx-edit-alt me-1"></i> Chỉnh sửa
                        </span>
                    </div>

                    <ul class="list-unstyled mb-4 mt-3">
                        <li class="d-flex align-items-center mb-3">
                            <i class="bx bx-user"></i><span class="fw-medium mx-2">Họ tên:</span>
                            <span>{{ $user->full_name ?? '—' }}</span>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <i class="bx bx-envelope"></i><span class="fw-medium mx-2">Email:</span>
                            <span>{{ $user->email }}</span>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <i class="bx bx-phone"></i><span class="fw-medium mx-2">Số điện thoại:</span>
                            <span>{{ $user->phone ?? '—' }}</span>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <i class='bx bx-user-circle'></i><span class="fw-medium mx-2">Giới tính:</span>
                            @php
                                $genderMap = [0 => 'Nam', 1 => 'Nữ', 2 => 'Khác'];
                            @endphp
                            <span>{{ $genderMap[$user->gender] ?? '—' }}</span>
                        </li>
                        <li class="d-flex align-items-center mb-0">
                            <i class="bx bx-cake"></i><span class="fw-medium mx-2">Ngày sinh:</span>
                            <span>{{ $user->birthday ? \Carbon\Carbon::parse($user->birthday)->format('d/m/Y') : '—' }}</span>
                        </li>
                    </ul>

                    <small class="text-muted text-uppercase">Mô tả</small>
                    <p class="mt-2 mb-0">{{ $user->description ?? 'Chưa cập nhật mô tả.' }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mạng xã hội</h5>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="bx bxl-twitter text-info me-2 fs-4"></i>
                                <div>
                                    <small class="text-muted d-block">Twitter</small>
                                    <span>{{ $user->twitter_url ?? 'Chưa cập nhật' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="bx bxl-facebook text-primary me-2 fs-4"></i>
                                <div>
                                    <small class="text-muted d-block">Facebook</small>
                                    <span>{{ $user->facebook_url ?? 'Chưa cập nhật' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="bx bxl-instagram text-danger me-2 fs-4"></i>
                                <div>
                                    <small class="text-muted d-block">Instagram</small>
                                    <span>{{ $user->instagram_url ?? 'Chưa cập nhật' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <i class="bx bxl-linkedin text-primary me-2 fs-4"></i>
                                <div>
                                    <small class="text-muted d-block">LinkedIn</small>
                                    <span>{{ $user->linkedin_url ?? 'Chưa cập nhật' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal chỉnh sửa thông tin -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileLabel">Chỉnh sửa thông tin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="profileForm" action="{{ route('client.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <label class="form-label fw-semibold mb-2">Ảnh đại diện</label>
                                    <div id="avatar_preview" class="border rounded mb-3"
                                        style="width: 100%; aspect-ratio: 1/1; display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f5f5f9;">
                                        @if ($user->avatar)
                                            <img src="{{ $user->avatar }}" alt="Avatar preview"
                                                class="upload_btn w-100 h-100"
                                                style="object-fit: cover; border-radius: 0.5rem;"
                                                data-target-input="#avatar"
                                                data-target-preview="#avatar_preview">
                                        @else
                                            <i class="bx bx-image-add fs-1 text-muted"></i>
                                        @endif
                                    </div>
                                    <input type="hidden" name="avatar" id="avatar"
                                        value="{{ old('avatar', $user->avatar) }}">
                                    <div class="d-flex gap-2">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary upload_btn flex-grow-1"
                                            data-target-input="#avatar" data-target-preview="#avatar_preview">
                                            <i class="bx bx-upload me-1"></i> Upload hình ảnh
                                        </button>
                                        @if ($user->avatar)
                                            <a href="{{ $user->avatar }}" target="_blank"
                                                class="btn btn-sm btn-label-secondary">
                                                <i class="bx bx-link-external"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-8">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="full_name">Họ tên <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="full_name" id="full_name"
                                            class="form-control @error('full_name') is-invalid @enderror"
                                            value="{{ old('full_name', $user->full_name) }}" required
                                            maxlength="150">
                                        @error('full_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="email">
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" {{ $user->email_verified_at ? '' : 'name=email' }}
                                            id="email" class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', $user->email) }}"
                                            {{ $user->email_verified_at ? 'disabled' : 'required' }} maxlength="254">
                                        @if ($user->email_verified_at)
                                            {{-- Hidden input để submit email khi disabled --}}
                                            <input type="hidden" name="email" value="{{ $user->email }}">
                                            <small class="text-success d-block mt-1">
                                                <i class="bx bx-info-circle"></i> Email đã được xác thực không thể thay
                                                đổi
                                            </small>
                                        @endif
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="phone">Số điện thoại</label>
                                        <input type="text" name="phone" id="phone"
                                            class="form-control @error('phone') is-invalid @enderror"
                                            value="{{ old('phone', $user->phone) }}" maxlength="20">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="gender">Giới tính</label>
                                        <select name="gender" id="gender"
                                            class="form-select @error('gender') is-invalid @enderror">
                                            <option value="" @selected(old('gender', $user->gender) === null)>Chọn giới tính</option>
                                            <option value="0" @selected(old('gender', $user->gender) === 0 || old('gender', $user->gender) === '0')>Nam</option>
                                            <option value="1" @selected(old('gender', $user->gender) === 1 || old('gender', $user->gender) === '1')>Nữ</option>
                                            <option value="2" @selected(old('gender', $user->gender) === 2 || old('gender', $user->gender) === '2')>Khác</option>
                                        </select>
                                        @error('gender')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="birthday">Ngày sinh</label>
                                        <input type="text" name="birthday" id="birthday"
                                            class="form-control date-picker @error('birthday') is-invalid @enderror"
                                            placeholder="dd/mm/yyyy" value="{{ old('birthday', $user->birthday) }}">
                                        @error('birthday')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="description">Mô tả</label>
                                        <textarea name="description" id="description" rows="3"
                                            class="form-control @error('description') is-invalid @enderror" placeholder="Giới thiệu ngắn gọn"
                                            maxlength="255">{{ old('description', $user->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <label class="form-label fw-semibold mb-2">Mạng xã hội</label>
                                    <div class="row g-3">
                                        <div class="col-md-6 col-lg-6">
                                            <label class="form-label" for="twitter_url">Twitter URL</label>
                                            <input type="url" name="twitter_url" id="twitter_url"
                                                class="form-control @error('twitter_url') is-invalid @enderror"
                                                value="{{ old('twitter_url', $user->twitter_url) }}">
                                            @error('twitter_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 col-lg-6">
                                            <label class="form-label" for="facebook_url">Facebook URL</label>
                                            <input type="url" name="facebook_url" id="facebook_url"
                                                class="form-control @error('facebook_url') is-invalid @enderror"
                                                value="{{ old('facebook_url', $user->facebook_url) }}">
                                            @error('facebook_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 col-lg-6">
                                            <label class="form-label" for="instagram_url">Instagram URL</label>
                                            <input type="url" name="instagram_url" id="instagram_url"
                                                class="form-control @error('instagram_url') is-invalid @enderror"
                                                value="{{ old('instagram_url', $user->instagram_url) }}">
                                            @error('instagram_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 col-lg-6">
                                            <label class="form-label" for="linkedin_url">LinkedIn URL</label>
                                            <input type="url" name="linkedin_url" id="linkedin_url"
                                                class="form-control @error('linkedin_url') is-invalid @enderror"
                                                value="{{ old('linkedin_url', $user->linkedin_url) }}">
                                            @error('linkedin_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary" id="profileSubmitBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="btn-text">Lưu thay đổi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

