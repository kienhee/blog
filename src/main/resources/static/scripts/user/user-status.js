/* Kienhee admin — approve / disable / enable accounts on the Users page */
$(function () {
  'use strict';

  var $form = $('#user-status-form');
  if (!$form.length) return;

  $(document).on('click', '.btn-user-status', function () {
    var $btn = $(this);
    var status = $btn.attr('data-status');
    $form.attr('action', '/admin/users/' + $btn.attr('data-id') + '/status');
    $form.find('[name="status"]').val(status);

    if (status === 'DISABLED' && window.khDialog) {
      window.khDialog('sign-in for "' + ($btn.attr('data-name') || 'this account') + '"', function () { $form[0].submit(); },
        khT('js.user.disable_note'));
      return;
    }
    $form[0].submit();
  });
});
