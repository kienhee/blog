/* Kienhee admin — role delete confirmation */
$(function () {
  'use strict';

  $('#btn-delete-role').on('click', function () {
    var id = $(this).data('id');
    var name = $(this).data('name') || '';
    $('#delete-role-form').attr('action', '/admin/roles/' + id + '/delete');
    if (window.khDialog) {
      window.khDialog(name ? 'role "' + name + '"' : 'this role', '#delete-role-form',
        khT('js.role.delete_note'));
    }
  });
});
