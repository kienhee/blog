/* Kienhee auth — validation for login, register, and forgot password forms */
$(function () {
  'use strict';

  if (!$.fn.validate || !$('#auth-form').length) return;

  var $authForm = $('#auth-form');
  var action = ($authForm.attr('action') || '').toLowerCase();
  var hasFullName = $authForm.find('[name="fullName"]').length > 0;
  var isRegister = action.indexOf('register') !== -1 || hasFullName;
  var isForgot = action.indexOf('forgot') !== -1;

  var validationRules = {
    email: {
      required: true,
      email: true
    }
  };

  var validationMessages = {
    email: {
      required: 'Email is required.',
      email: 'Invalid email format.'
    }
  };

  if (isRegister) {
    validationRules.fullName = {
      required: true,
      minlength: 2
    };
    validationMessages.fullName = {
      required: 'Full name is required.',
      minlength: 'Full name must have at least 2 characters.'
    };
    validationRules.password = {
      required: true,
      minlength: 6
    };
    validationMessages.password = {
      required: 'Password is required.',
      minlength: 'Password must have at least 6 characters.'
    };
  } else if (!isForgot) {
    // Login form
    validationRules.password = {
      required: true
    };
    validationMessages.password = {
      required: 'Password is required.'
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

