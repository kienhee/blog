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
        'This cannot be undone. Replies to this comment are deleted with it.');
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
          'This cannot be undone. Replies to these comments are deleted with them.');
      }
      return;
    }

    $form.attr('action', '/admin/comments/bulk-status');
    $('<input type="hidden" name="status">').val(action).appendTo($form);
    $form[0].submit();
  });
});
