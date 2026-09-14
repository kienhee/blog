/* Kienhee admin — settings page.
 *
 * Validation rules live on the fields themselves (data-rule-* / data-msg-*, read by jQuery Validate),
 * so a new setting only needs its markup in settings.html: a field named settings[<key>] plus any rules.
 */
$(function () {
  'use strict';

  var $form = $('#settings-form');
  if (!$form.length || !$.fn.validate) return;

  $form.validate({
    errorElement: 'span',
    errorClass: 'err show',
    highlight: function (element) {
      $(element).addClass('error').closest('.field').addClass('has-error');
    },
    unhighlight: function (element) {
      $(element).removeClass('error').closest('.field').removeClass('has-error');
    },
    errorPlacement: function (error, element) {
      error.appendTo($(element).closest('.field'));
    }
  });
});
