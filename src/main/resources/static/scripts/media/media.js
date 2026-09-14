/* Kienhee admin — Media library page.
 *
 * The explorer itself lives in media-explorer.js (the shared MediaExplorer component, also used by
 * pickers such as the post cover image). This page only mounts it in "manage" mode.
 */
$(function () {
  'use strict';

  var el = document.getElementById('media-library');
  if (!el || !window.MediaExplorer) return;

  window.MediaExplorer.mount(el, {
    mode: 'manage',
    // "?folder=<id>" (kept by the redirecting form endpoints) reopens that folder.
    initialFolder: el.getAttribute('data-initial-folder') || '',
    trashUrl: el.getAttribute('data-trash-url') || '/admin/media/trash'
  });
});
