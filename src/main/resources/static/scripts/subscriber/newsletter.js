/* Kienhee admin — newsletter: confirm before sending, delete subscribers */
$(function () {
  'use strict';

  var $send = $('#newsletter-send-form');
  var confirmed = false;
  $send.on('submit', function (e) {
    if (confirmed) return;
    e.preventDefault();
    var subject = $.trim($('#newsletter-subject').val());
    var body = $.trim($('#newsletter-body').val());
    if (subject.length < 3 || body.length < 10) {
      if (window.khToast) window.khToast('Add a subject and a few words first');
      return;
    }
    if (!window.khDialog) {
      confirmed = true;
      $send[0].submit();
      return;
    }
    window.khDialog('"' + subject + '" to every confirmed subscriber', function () {
      confirmed = true;
      $('#btn-send-newsletter').prop('disabled', true).text('Sending…');
      $send[0].submit();
    }, 'Emails go out right away and cannot be recalled.');
  });

  $(document).on('click', '.btn-del-subscriber', function () {
    var $btn = $(this);
    $('#delete-subscriber-form').attr('action', '/admin/subscribers/' + $btn.attr('data-id') + '/delete');
    if (window.khDialog) {
      window.khDialog($btn.attr('data-name'), '#delete-subscriber-form',
        'They are removed from the list completely. To get issues again they must sign up and confirm.');
    }
  });
});
