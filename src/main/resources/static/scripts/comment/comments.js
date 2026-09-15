/* Kienhee admin — comment moderation (single + bulk) */
$(function () {
  'use strict';

  var $table = $('table.dt');

  // Checked rows across every DataTables page, not only the visible one.
  function selectedIds() {
    var nodes = $.fn.DataTable && $.fn.DataTable.isDataTable($table) ? $table.DataTable().rows().nodes() : $table.find('tbody tr');
    return $(nodes).find('.rowcheck:checked').map(function () { return this.value; }).get();
  }

  $(document).on('click', '.btn-comment-status', function () {
    var $btn = $(this);
    var $form = $('#comment-status-form');
    $form.attr('action', '/admin/comments/' + $btn.attr('data-id') + '/status');
    $form.find('[name="status"]').val($btn.attr('data-status'));
    $form[0].submit();
  });

  $(document).on('click', '.btn-del-comment', function () {
    var $btn = $(this);
    $('#comment-delete-form').attr('action', '/admin/comments/' + $btn.attr('data-id') + '/delete');
    if (window.khDialog) {
      window.khDialog('the comment by "' + ($btn.attr('data-name') || 'unknown') + '"', '#comment-delete-form',
        'It moves to the trash with its replies. You can restore it from the Trash page.');
    }
  });

  $('[data-comment-bulk]').on('click', function () {
    var action = $(this).attr('data-comment-bulk');
    var ids = selectedIds();
    if (!ids.length) {
      if (window.khToast) window.khToast('Select at least one comment');
      return;
    }

    var $form = $('#comment-bulk-form');
    $form.find('input[name="ids"], input[name="status"]').remove();
    $.each(ids, function (_, id) {
      $('<input type="hidden" name="ids">').val(id).appendTo($form);
    });

    if (action === 'delete') {
      $form.attr('action', '/admin/comments/bulk-delete');
      if (window.khDialog) {
        window.khDialog(ids.length === 1 ? '1 selected comment' : ids.length + ' selected comments', '#comment-bulk-form',
          'They move to the trash with their replies. You can restore them from the Trash page.');
      }
      return;
    }

    $form.attr('action', '/admin/comments/bulk-status');
    $('<input type="hidden" name="status">').val(action).appendTo($form);
    $form[0].submit();
  });
});

/* Reply from the moderation page: one modal, its action points at the chosen comment. */
$(function () {
  'use strict';

  var $modal = $('#comment-reply-modal');
  if (!$modal.length) return;
  var $form = $('#comment-reply-form');
  var $text = $('#reply-content');

  function close() {
    $modal.removeClass('open');
  }

  $(document).on('click', '.btn-reply-comment', function () {
    var $btn = $(this);
    $form.attr('action', '/admin/comments/' + $btn.attr('data-id') + '/reply');
    $('#reply-context').text('Replying to ' + ($btn.attr('data-name') || 'this comment') + ' on "' + ($btn.attr('data-post') || '') + '"');
    $text.val('');
    $modal.addClass('open');
    setTimeout(function () { $text.trigger('focus'); }, 50);
  });

  $('#reply-cancel').on('click', close);
  $modal.on('mousedown', function (e) { if (e.target === this) close(); });
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $modal.hasClass('open')) close();
  });

  $form.on('submit', function (e) {
    if (!$.trim($text.val())) {
      e.preventDefault();
      if (window.khToast) window.khToast('Write a reply first');
      $text.trigger('focus');
    }
  });
});
