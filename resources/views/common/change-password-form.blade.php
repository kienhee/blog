{{-- Common Change Password Form Component --}}
{{-- Usage: @include('common.change-password-form', ['formAction' => route('...'), 'formId' => 'formChangePassword']) --}}

<form id="{{ $formId ?? 'formChangePassword' }}" method="POST" action="{{ $formAction }}">
    @csrf
    <div class="row">
        <div class="mb-3 col-md-6">
            <label class="form-label" for="currentPassword">
                Mật khẩu hiện tại <span class="text-danger">*</span>
            </label>
            <input
                class="form-control @error('currentPassword') is-invalid @enderror"
                type="password"
                name="currentPassword"
                id="currentPassword"
                placeholder="Nhập mật khẩu hiện tại"
            />
            @error('currentPassword')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="row">
        <div class="mb-3 col-md-6">
            <label class="form-label" for="newPassword">
                Mật khẩu mới <span class="text-danger">*</span>
            </label>
            <input
                class="form-control @error('newPassword') is-invalid @enderror"
                type="password"
                id="newPassword"
                name="newPassword"
                placeholder="Nhập mật khẩu mới"
            />
            @error('newPassword')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 col-md-6">
            <label class="form-label" for="newPassword_confirmation">
                Xác nhận mật khẩu mới <span class="text-danger">*</span>
            </label>
            <input
                class="form-control @error('newPassword_confirmation') is-invalid @enderror"
                type="password"
                name="newPassword_confirmation"
                id="newPassword_confirmation"
                placeholder="Nhập lại mật khẩu mới"
            />
            @error('newPassword_confirmation')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12 mt-1">
            <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                Lưu thay đổi
            </button>
            <button type="reset" class="btn btn-label-secondary" id="resetBtn">Hủy</button>
        </div>
    </div>
</form>

