{{-- Hiển thị lỗi validate --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

{{-- Hiển thị lỗi hệ thống khác --}}
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

{{-- Hiển thị thông báo thành công --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
@endif

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Tự động ẩn alert sau 4 giây
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach((alertEl) => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                    bsAlert.close();
                }, 10000); // 10000ms = 10 giây
            });
        });
    </script>
@endpush
