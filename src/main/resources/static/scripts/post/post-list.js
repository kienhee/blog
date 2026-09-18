/* Kienhee admin — post list delete handling */
$(function () {
  'use strict';

  $(document).on('click', '.btn-del-post', function () {
    var id = $(this).data('id');
    var name = $(this).data('name') || '';
    $('#delete-post-form').attr('action', '/admin/post/' + id + '/delete');
    if (window.khDialog) {
      window.khDialog(name ? 'post "' + name + '"' : 'this post', '#delete-post-form',
        khT('js.post.delete_note'));
    }
  });
});
