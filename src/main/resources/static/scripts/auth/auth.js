/* Kienhee auth — validation for login, register, and forgot password forms */
$(function () {
  'use strict';

  if (!$.fn.validate || !$('#auth-form').length) return;

  var $authForm = $('#auth-form');
  var action = ($authForm.attr('action') || '').toLowerCase();
  var hasFullName = $authForm.find('[name="fullName"]').length > 0;
  var isRegister = action.indexOf('register') !== -1 || hasFullName;
  var isForgot = action.indexOf('forgot') !== -1;
  var isReset = action.indexOf('/auth/reset') !== -1;

  var validationRules = {
    email: {
      required: true,
      email: true
    }
  };

  var validationMessages = {
    email: {
      required: khT('validation.email.required'),
      email: khT('validation.email.invalid')
    }
  };

  if (isReset) {
    validationRules.password = { required: true, minlength: 8, maxlength: 72 };
    validationMessages.password = {
      required: khT('validation.password.required'),
      minlength: khT('js.auth.password_min8'),
      maxlength: khT('js.auth.password_max72')
    };
    validationRules.confirmPassword = { required: true, equalTo: '#password' };
    validationMessages.confirmPassword = {
      required: khT('validation.password.confirm_new'),
      equalTo: "The two passwords don't match."
    };
  } else if (isRegister) {
    validationRules.fullName = {
      required: true,
      minlength: 2
    };
    validationMessages.fullName = {
      required: khT('validation.full_name.required'),
      minlength: khT('validation.full_name.min')
    };
    validationRules.password = {
      required: true,
      minlength: 6
    };
    validationMessages.password = {
      required: khT('validation.password.required'),
      minlength: khT('validation.password.min6')
    };
  } else if (!isForgot) {
    // Login form
    validationRules.password = {
      required: true
    };
    validationMessages.password = {
      required: khT('validation.password.required')
    };
  }

  $authForm.validate({
    rules: validationRules,
    messages: validationMessages,
    errorElement: 'span',
    errorClass: 'err show',
    highlight: function (element) {
      var $el = $(element);
      $el.addClass('error');
      $el.closest('.field').addClass('has-error');
    },
    unhighlight: function (element) {
      var $el = $(element);
      $el.removeClass('error');
      $el.closest('.field').removeClass('has-error');
    },
    errorPlacement: function (error, element) {
      var $field = $(element).closest('.field');
      $field.find('span.err:not([id])').remove();
      error.appendTo($field);
    }
  });
});

