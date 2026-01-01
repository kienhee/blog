/**
 * Client Auth Pages JavaScript
 * Handles password toggle functionality and form validation for login, register, forgot password, and reset password pages
 */
"use strict";

$(function () {
    // ================================
    // Password Toggle Functionality
    // ================================
    function initPasswordToggle() {
        // Try to use Helpers if available
        if (typeof window.Helpers !== 'undefined' && typeof window.Helpers.initPasswordToggle === 'function') {
            window.Helpers.initPasswordToggle();
            return;
        }
        
        // Fallback: Manual implementation
        const passwordToggleContainers = document.querySelectorAll('.form-password-toggle');
        
        passwordToggleContainers.forEach(function(container) {
            const toggleIcon = container.querySelector('.input-group-text i');
            const passwordInput = container.querySelector('input[type="password"], input[type="text"]');
            
            if (!toggleIcon || !passwordInput) return;
            
            // Add click event to the span container (more reliable than just icon)
            const toggleSpan = container.querySelector('.input-group-text');
            if (toggleSpan) {
                toggleSpan.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle input type
                    if (passwordInput.getAttribute('type') === 'password') {
                        passwordInput.setAttribute('type', 'text');
                        toggleIcon.classList.remove('bx-hide');
                        toggleIcon.classList.add('bx-show');
                    } else {
                        passwordInput.setAttribute('type', 'password');
                        toggleIcon.classList.remove('bx-show');
                        toggleIcon.classList.add('bx-hide');
                    }
                });
            }
        });
    }

    // Initialize password toggle when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggle);
    } else {
        initPasswordToggle();
    }
    
    // Also try after a short delay in case Helpers loads later
    setTimeout(function() {
        if (typeof window.Helpers !== 'undefined' && typeof window.Helpers.initPasswordToggle === 'function') {
            window.Helpers.initPasswordToggle();
        }
    }, 500);

    // ================================
    // Form Validation Setup
    // ================================
    
    // Check if FormValidation is available
    if (typeof FormValidation === 'undefined') {
        console.warn('FormValidation library is not loaded. Form validation will be skipped.');
        return;
    }

    // ================================
    // Login Form Validation
    // ================================
    const $loginForm = $('#loginForm');
    if ($loginForm.length) {
        const loginValidationRules = {
            email: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập email',
                    },
                    emailAddress: {
                        message: 'Email không hợp lệ',
                    },
                },
            },
            password: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập mật khẩu',
                    },
                },
            },
        };

        const fvLogin = FormValidation.formValidation($loginForm[0], {
            fields: loginValidationRules,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: '.mb-3',
                    eleInvalidClass: 'is-invalid',
                    eleValidClass: 'is-valid',
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton(),
                defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
            },
            init: (instance) => {
                instance.on('plugins.message.placed', function (e) {
                    // If input is inside input-group, place message after the group
                    if (
                        e.element.parentElement &&
                        e.element.parentElement.classList.contains('input-group')
                    ) {
                        e.element.parentElement.insertAdjacentElement(
                            'afterend',
                            e.messageElement
                        );
                    }
                });
            },
        });

        // Store instance globally for potential reuse
        window.fvLogin = fvLogin;
    }

    // ================================
    // Register Form Validation
    // ================================
    const $registerForm = $('#registerForm');
    if ($registerForm.length) {
        const $phoneInput = $('#phone');

        // Phone number input - chỉ cho phép nhập số và giới hạn 10 số
        if ($phoneInput.length) {
            const filterNumericOnly = (value) => value.replace(/[^0-9]/g, '');
            
            const handlePhoneInput = (value) => {
                const numericOnly = filterNumericOnly(value).substring(0, 10);
                $phoneInput.val(numericOnly);
                if (window.fvRegister) {
                    window.fvRegister.revalidateField('phone');
                }
            };

            $phoneInput.on('input', function () {
                handlePhoneInput($(this).val());
            });

            $phoneInput.on('paste', function (e) {
                e.preventDefault();
                const pastedText = (e.originalEvent || e).clipboardData.getData('text');
                handlePhoneInput(pastedText);
            });

            $phoneInput.on('keypress', function (e) {
                if (!/[0-9]/.test(String.fromCharCode(e.which))) {
                    e.preventDefault();
                }
            });
        }

        const registerValidationRules = {
            full_name: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập họ và tên',
                    },
                    stringLength: {
                        min: 2,
                        max: 150,
                        message: 'Họ và tên phải từ 2 đến 150 ký tự',
                    },
                },
            },
            email: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập email',
                    },
                    emailAddress: {
                        message: 'Email không hợp lệ',
                    },
                    stringLength: {
                        max: 254,
                        message: 'Email không được vượt quá 254 ký tự',
                    },
                },
            },
            phone: {
                validators: {
                    stringLength: {
                        min: 10,
                        max: 10,
                        message: 'Số điện thoại phải có đúng 10 số',
                    },
                    regexp: {
                        regexp: /^(032|033|034|035|036|037|038|039|086|096|097|098|081|082|083|084|085|088|091|094|056|058|092|070|076|077|078|079|089|090|093|099|059)[0-9]{7}$/,
                        message: 'Số điện thoại không hợp lệ. Vui lòng nhập đúng đầu số của các nhà mạng Việt Nam',
                    },
                },
            },
            password: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập mật khẩu',
                    },
                    stringLength: {
                        min: 6,
                        max: 255,
                        message: 'Mật khẩu phải có ít nhất 6 ký tự và không quá 255 ký tự',
                    },
                },
            },
            password_confirmation: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập xác nhận mật khẩu',
                    },
                    identical: {
                        compare: function () {
                            return $registerForm.find('[name="password"]').val();
                        },
                        message: 'Mật khẩu xác nhận không khớp',
                    },
                },
            },
        };

        const fvRegister = FormValidation.formValidation($registerForm[0], {
            fields: registerValidationRules,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: '.mb-3',
                    eleInvalidClass: 'is-invalid',
                    eleValidClass: 'is-valid',
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton(),
                defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
            },
            init: (instance) => {
                instance.on('plugins.message.placed', function (e) {
                    // If input is inside input-group, place message after the group
                    if (
                        e.element.parentElement &&
                        e.element.parentElement.classList.contains('input-group')
                    ) {
                        e.element.parentElement.insertAdjacentElement(
                            'afterend',
                            e.messageElement
                        );
                    }
                });
            },
        });

        // Store instance globally for potential reuse
        window.fvRegister = fvRegister;
    }

    // ================================
    // Forgot Password Form Validation
    // ================================
    const $forgotPasswordForm = $('#forgotPasswordForm');
    if ($forgotPasswordForm.length) {
        const forgotPasswordValidationRules = {
            email: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập email của bạn.',
                    },
                    emailAddress: {
                        message: 'Email không đúng định dạng.',
                    },
                },
            },
        };

        const fvForgotPassword = FormValidation.formValidation($forgotPasswordForm[0], {
            fields: forgotPasswordValidationRules,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: '.mb-3',
                    eleInvalidClass: 'is-invalid',
                    eleValidClass: 'is-valid',
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton(),
                defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
            },
        });

        // Store instance globally for potential reuse
        window.fvForgotPassword = fvForgotPassword;
    }

    // ================================
    // Reset Password Form Validation
    // ================================
    const $resetPasswordForm = $('#resetPasswordForm');
    if ($resetPasswordForm.length) {
        const resetPasswordValidationRules = {
            token: {
                validators: {
                    notEmpty: {
                        message: 'Token không hợp lệ.',
                    },
                },
            },
            email: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập email.',
                    },
                    emailAddress: {
                        message: 'Email không đúng định dạng.',
                    },
                },
            },
            password: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập mật khẩu mới.',
                    },
                    stringLength: {
                        min: 6,
                        message: 'Mật khẩu phải có ít nhất 6 ký tự.',
                    },
                },
            },
            password_confirmation: {
                validators: {
                    notEmpty: {
                        message: 'Vui lòng nhập xác nhận mật khẩu.',
                    },
                    identical: {
                        compare: function () {
                            return $resetPasswordForm.find('[name="password"]').val();
                        },
                        message: 'Mật khẩu xác nhận không khớp.',
                    },
                    stringLength: {
                        min: 6,
                        message: 'Mật khẩu phải có ít nhất 6 ký tự.',
                    },
                },
            },
        };

        const fvResetPassword = FormValidation.formValidation($resetPasswordForm[0], {
            fields: resetPasswordValidationRules,
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    rowSelector: '.mb-3',
                    eleInvalidClass: 'is-invalid',
                    eleValidClass: 'is-valid',
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton(),
                defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
            },
            init: (instance) => {
                instance.on('plugins.message.placed', function (e) {
                    // If input is inside input-group, place message after the group
                    if (
                        e.element.parentElement &&
                        e.element.parentElement.classList.contains('input-group')
                    ) {
                        e.element.parentElement.insertAdjacentElement(
                            'afterend',
                            e.messageElement
                        );
                    }
                });
            },
        });

        // Store instance globally for potential reuse
        window.fvResetPassword = fvResetPassword;
    }

    // ================================
    // Clear server errors on input (for all forms)
    // ================================
    const allAuthForms = $loginForm.add($registerForm).add($forgotPasswordForm).add($resetPasswordForm);
    allAuthForms.find('input, textarea').on('input', function () {
        const $input = $(this);
        // Remove server validation errors when user starts typing
        if ($input.hasClass('is-invalid')) {
            const $feedback = $input.parent().next('.invalid-feedback');
            if (!$feedback.length) {
                const $group = $input.closest('.input-group');
                if ($group.length) {
                    $feedback = $group.next('.invalid-feedback');
                }
            }
            if ($feedback.length && $feedback.hasClass('d-block')) {
                // Only remove if it's a server error (has class d-block)
                $feedback.remove();
                $input.removeClass('is-invalid');
                // Revalidate field to update FormValidation state
                const formInstance = window.fvLogin || window.fvRegister || window.fvForgotPassword || window.fvResetPassword;
                if (formInstance) {
                    formInstance.revalidateField($input[0]);
                }
            }
        }
    });
});
