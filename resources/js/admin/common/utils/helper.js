/**
 * Format money input fields
 * Chỉ cho phép nhập số
 * max 11 số
 * số 0 đầu tiên thì không cho nhập tiếp, số khác thì xóa số 0 ở đầu
 * không cho nhập số âm
 * số thập phân
 */
$(document).ready(function() {
    $('.format-money').on('input', function () {
        let val = this.value;

        // 1. Xoá tất cả ký tự không phải số
        val = val.replace(/\D/g, '');

        // 2. Bỏ số 0 ở đầu (chỉ cho phép "0")
        val = val.replace(/^0+(?!$)/, '');

        // 3. Giới hạn tối đa 11 số
        val = val.substring(0, 11);

        // 4. Thêm dấu phân tách hàng nghìn (quan trọng: phải xoá chấm cũ trước khi thêm lại)
        val = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        this.value = val;
    });
});
