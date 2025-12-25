/**
 * Form Loading State Handler
 * Xử lý loading state cho tất cả các button submit trong form
 */

/**
 * Khởi tạo loading state cho form
 * @param {HTMLElement|string} form - Form element hoặc selector
 * @param {Object} options - Tùy chọn
 */
export function initFormLoading(form, options = {}) {
    const formElement = typeof form === 'string' ? document.querySelector(form) : form;

    if (!formElement) {
        console.warn('Form element not found');
        return;
    }

    const {
        buttonSelector = 'button[type="submit"]',
        buttonId = 'btnSubmit',
        loadingText = 'Đang xử lý...',
        useFormValidation = false
    } = options;

    // Tìm button submit
    let submitButton = formElement.querySelector(`#${buttonId}`) || formElement.querySelector(buttonSelector);

    if (!submitButton) {
        return;
    }

    // Tạo cấu trúc button với loading state nếu chưa có
    if (!submitButton.querySelector('.btn-text') && !submitButton.querySelector('.btn-loading')) {
        const originalText = submitButton.textContent.trim();
        submitButton.innerHTML = `
            <span class="btn-text">${originalText}</span>
            <span class="btn-loading d-none">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                ${loadingText}
            </span>
        `;
    }

    const btnText = submitButton.querySelector('.btn-text');
    const btnLoading = submitButton.querySelector('.btn-loading');

    if (!btnText || !btnLoading) {
        return;
    }

    // Function để hiển thị loading
    const showLoading = () => {
        submitButton.disabled = true;
        if (btnText) btnText.classList.add('d-none');
        if (btnLoading) btnLoading.classList.remove('d-none');
    };

    // Function để ẩn loading
    const hideLoading = () => {
        submitButton.disabled = false;
        if (btnText) btnText.classList.remove('d-none');
        if (btnLoading) btnLoading.classList.add('d-none');
    };

    // Nếu sử dụng FormValidation
    if (useFormValidation && window.FormValidation) {
        // Giả sử FormValidation instance đã được khởi tạo và truyền vào options
        const fv = options.formValidationInstance;
        if (fv) {
            fv.on('core.form.valid', showLoading);
            fv.on('core.form.invalid', hideLoading);
        }
    } else {
        // Xử lý submit event trực tiếp
        formElement.addEventListener('submit', function(e) {
            // Chỉ hiển thị loading nếu form hợp lệ (nếu có validation)
            // Hoặc luôn hiển thị nếu không có validation
            showLoading();

            // Fallback: Reset loading sau 10 giây nếu form không submit được
            setTimeout(() => {
                hideLoading();
            }, 10000);
        });
    }

    // Expose methods để có thể sử dụng từ bên ngoài
    return {
        showLoading,
        hideLoading,
        button: submitButton
    };
}

// Expose functions to window global for inline scripts
if (typeof window !== 'undefined') {
    window.initFormLoading = initFormLoading;
}


