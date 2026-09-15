/* Kienhee admin — MediaExplorer
 *
 * The media library as ONE reusable component, rendered entirely from the JSON API under
 * /admin/api/media (MediaApiController). The Media library page mounts it in "manage" mode;
 * pickers such as the post cover image mount the very same component in "pick" mode — so a
 * change made here shows up everywhere the library is used.
 *
 *   var explorer = MediaExplorer.mount(element, {
 *     mode: 'manage' | 'pick',
 *     accept: null | 'image',          // pick: only offer images
 *     multiple: false,                 // pick: allow choosing several files
 *     initialFolder: '',               // folder id to open first ('' = Home)
 *     trashUrl: '/admin/media/trash',  // manage: sidebar Trash link
 *     onSelect: function (files) {},   // pick: called with the chosen file objects
 *     onCancel: function () {}         // pick: "Cancel" pressed
 *   });
 *   explorer.reload(); explorer.clearSelection(); explorer.destroy();
 *
 * Everything the component creates lives inside `element` and every lookup is scoped to it
 * ([data-mx="..."] hooks, no ids), so it never collides with the host page or another instance.
 * It relies on the admin layout for jQuery, khToast/khDialog (core/admin.js), jQuery Validate
 * and, when present, flatpickr.
 */
(function (window, $) {
  'use strict';

  var API = '/admin/api/media';
  var PAGE_SIZES = [12, 24, 48, 96];
  var DEFAULT_PAGE_SIZE = 24;
  var instances = 0;

  var IMAGE_TYPES = [
    ['image/jpeg', 'JPG'], ['image/png', 'PNG'], ['image/webp', 'WEBP'], ['image/gif', 'GIF'], ['image/svg+xml', 'SVG']
  ];
  var FILE_TYPES = [
    ['application/pdf', 'PDF'], ['application/msword', 'DOC'],
    ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'DOCX'],
    ['application/vnd.ms-excel', 'XLS'],
    ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'XLSX'],
    ['application/zip', 'ZIP'], ['text/plain', 'TXT']
  ];

  var I = function (paths, extra) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"' +
      (extra || '') + '>' + paths + '</svg>';
  };
  var MENU = ' width="15" height="15" style="margin-right:8px"';
  var ICONS = {
    open: I('<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/>', MENU),
    rename: I('<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>', MENU),
    move: I('<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>', MENU),
    download: I('<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>', MENU),
    delete: I('<path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>', MENU),
    info: I('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>', MENU),
    folder: I('<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>', MENU),
    select: I('<path d="M20 6 9 17l-5-5"/>', MENU),
    edit: I('<path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/>', MENU),
    preview: I('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M11 8v6"/><path d="M8 11h6"/>', MENU)
  };
  var FOLDER_TILE = '<svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>';
  var FILE_TILE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';

  /* ------------------------------------------------------------------ helpers */

  function csrfToken() {
    return $('meta[name="_csrf"]').attr('content') || $('input[name="_csrf"]').first().val() || '';
  }

  function post(url, data) {
    var token = csrfToken();
    return $.ajax({
      url: url,
      method: 'POST',
      data: $.extend({ _csrf: token }, data || {}),
      headers: { 'X-CSRF-TOKEN': token },
      traditional: true, // ids=1&ids=2, the shape Spring binds to List<Long>
      dataType: 'json'
    });
  }

  function notify(message) {
    if (window.khToast && message) window.khToast(message);
  }

  function errorOf(xhr, fallback) {
    return (xhr && xhr.responseJSON && xhr.responseJSON.message) || fallback;
  }

  /** Preview URL that changes when the image is edited in place (same URL, new bytes). */
  function versioned(url, file) {
    if (!url || !file || !file.version) return url;
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'v=' + encodeURIComponent(file.version);
  }

  function norm(value) {
    return (value === undefined || value === null || value === '') ? '' : String(value);
  }

  function segments(path) {
    return String(path || '').split('/').filter(function (p) { return p !== ''; });
  }

  /** Page numbers to show around `current`, with '…' gaps: 1 … 4 5 6 … 20. */
  function pageList(current, total) {
    var out = [];
    var i;
    if (total <= 7) {
      for (i = 1; i <= total; i++) out.push(i);
      return out;
    }
    var start = Math.max(2, current - 1);
    var end = Math.min(total - 1, current + 1);
    if (current <= 3) { start = 2; end = 4; }
    if (current >= total - 2) { start = total - 3; end = total - 1; }
    out.push(1);
    if (start > 2) out.push('…');
    for (i = start; i <= end; i++) out.push(i);
    if (end < total - 1) out.push('…');
    out.push(total);
    return out;
  }

  function storedPageSize(key) {
    try {
      var v = Number(window.localStorage.getItem(key));
      return PAGE_SIZES.indexOf(v) > -1 ? v : DEFAULT_PAGE_SIZE;
    } catch (e) {
      return DEFAULT_PAGE_SIZE;
    }
  }

  function kb(bytes) {
    return Math.round((Number(bytes) || 0) / 1024) + ' KB';
  }

  var TEMPLATE =
    '<div class="media-explorer mx">' +
    '  <aside class="media-sidebar">' +
    '    <div class="ms-head"><span class="kicker">Folders</span></div>' +
    '    <ul class="folder-list" data-mx="folders"></ul>' +
    '    <p class="dim mx-hint" data-mx="hint"></p>' +
    '    <a class="trash-link mx-trash-link" data-mx="trash-link">' +
    I('<path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>', ' width="14" height="14"') +
    '      <span>Trash</span><span class="dim mx-trash-count" data-mx="trash-count"></span>' +
    '    </a>' +
    '    <p class="dim mx-trash-note" data-mx="trash-note"></p>' +
    '  </aside>' +
    '  <div class="media-content">' +
    '    <div class="explorer-toolbar">' +
    '      <button type="button" class="btn-icon-label" data-mx="new-folder">' +
    I('<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><line x1="12" y1="11" x2="12" y2="15"/><line x1="10" y1="13" x2="14" y2="13"/>') +
    '        New Folder</button>' +
    '      <button type="button" class="btn-icon-label" data-mx="upload">' +
    I('<path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M4 21h16"/>') +
    '        <span data-mx="upload-label">Upload</span></button>' +
    '      <span class="spacer"></span>' +
    '      <span class="mx-count" data-mx="count"></span>' +
    '      <button type="button" class="icon-btn-plain" data-mx="refresh" title="Refresh">' +
    I('<path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/>') + '</button>' +
    '    </div>' +
    '    <input type="file" data-mx="file-input" multiple hidden>' +
    '    <div class="dropzone mx-dropzone" data-mx="dropzone">' +
    '      <div><strong>Click to choose files</strong> or drag and drop them here</div>' +
    '      <div class="mx-dropzone-sub" data-mx="dropzone-sub"></div>' +
    '    </div>' +
    '    <div class="explorer-breadcrumb" data-mx="breadcrumb"></div>' +
    '    <div class="selection-bar" data-mx="selection-bar">' +
    '      <span class="count" data-mx="selection-count"></span>' +
    '      <button type="button" data-mx="sel-rename" data-manage data-perm="edit">' + I('<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>') + 'Rename</button>' +
    '      <select class="select mx-move-select" data-mx="sel-move-folder" data-manage data-perm="edit"></select>' +
    '      <button type="button" data-mx="sel-move" data-manage data-perm="edit">' + I('<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>') + 'Move</button>' +
    '      <button type="button" data-mx="sel-download" data-manage>' + I('<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>') + 'Download</button>' +
    '      <button type="button" class="danger" data-mx="sel-delete" data-manage data-perm="delete">' + I('<path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>') + 'Delete</button>' +
    '      <span class="spacer"></span>' +
    '      <button type="button" class="close-x" data-mx="sel-clear" title="Clear selection">' +
    I('<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>', ' width="14" height="14"') + '</button>' +
    '    </div>' +
    '    <div class="explorer-filters">' +
    '      <input class="input mx-f-search" data-mx="search" placeholder="Search by name…">' +
    '      <select class="select mx-f-kind" data-mx="filter-kind">' +
    '        <option value="">All kinds</option><option value="image">Images only</option><option value="file">Files only</option>' +
    '      </select>' +
    '      <select class="select mx-f-type" data-mx="filter-type"></select>' +
    '      <select class="select mx-f-usage" data-mx="filter-usage">' +
    '        <option value="">Any usage</option><option value="used">Used in a post</option><option value="unused">Unused</option>' +
    '      </select>' +
    '      <select class="select mx-f-uploader" data-mx="filter-uploader"></select>' +
    '      <input class="input mx-f-date" type="text" autocomplete="off" placeholder="From date" title="Uploaded from" data-mx="filter-from">' +
    '      <input class="input mx-f-date" type="text" autocomplete="off" placeholder="To date" title="Uploaded to" data-mx="filter-to">' +
    '      <select class="select mx-f-sort" data-mx="sort">' +
    '        <option value="newest">Newest first</option><option value="oldest">Oldest first</option>' +
    '        <option value="largest">Largest first</option><option value="smallest">Smallest first</option>' +
    '        <option value="name">Name (A-Z)</option>' +
    '      </select>' +
    '    </div>' +
    // Only this region scrolls: the toolbar, breadcrumb, selection bar and filters stay put while
    // the explorer fills the available height (full page on /admin/media, full modal in pickers).
    '    <div class="mx-scroll" data-mx="scroll">' +
    '      <div class="icon-grid" data-mx="grid"></div>' +
    '      <div class="dim mx-empty" data-mx="empty"></div>' +
    '    </div>' +
    // Pager sits below the scrolling grid, always visible.
    '    <div class="mx-pager" data-mx="pager">' +
    '      <span class="dim mx-pager-info" data-mx="page-info"></span>' +
    '      <label class="mx-pager-size"><span class="dim">Per page</span>' +
    '        <select class="select" data-mx="page-size" aria-label="Files per page"></select></label>' +
    '      <nav class="mx-pages" data-mx="pages" aria-label="Pages"></nav>' +
    '    </div>' +
    '    <div class="mx-pick-foot" data-mx="pick-foot">' +
    '      <span class="dim" data-mx="pick-summary"></span>' +
    '      <span class="spacer"></span>' +
    '      <button type="button" class="btn btn-ghost" data-mx="pick-cancel">Cancel</button>' +
    '      <button type="button" class="btn" data-mx="pick-confirm" disabled>Use selected</button>' +
    '    </div>' +
    '  </div>' +
    '  <div class="context-menu" data-mx="menu"></div>' +
    '  <form class="rename-popover" data-mx="rename-form" novalidate>' +
    '    <div class="dim mx-popover-title">Rename folder</div>' +
    '    <input class="input mx-rename-input" name="name" data-mx="rename-input" autocomplete="off">' +
    '    <div class="mx-popover-actions">' +
    '      <button class="btn btn-xs" type="submit">Save</button>' +
    '      <button class="btn btn-ghost btn-xs" type="button" data-mx="rename-cancel">Cancel</button>' +
    '    </div>' +
    '  </form>' +
    '  <div class="folder-modal" data-mx="folder-modal" role="dialog" aria-modal="true">' +
    '    <form class="box" data-mx="folder-form" novalidate>' +
    '      <div class="h">' +
    '        <div class="t" data-mx="folder-title">New folder</div>' +
    '        <p class="dim mx-folder-location" data-mx="folder-location">Creates the folder at Home.</p>' +
    '      </div>' +
    '      <div class="mx-folder-body">' +
    '        <input class="input mx-folder-input" name="name" data-mx="folder-input" placeholder="Folder name" autocomplete="off">' +
    '      </div>' +
    '      <div class="a">' +
    '        <button class="btn" type="submit">Create folder</button>' +
    '        <button class="btn btn-ghost" type="button" data-mx="folder-cancel">Cancel</button>' +
    '      </div>' +
    '    </form>' +
    '  </div>' +
    '  <div class="offcanvas" data-mx="detail" data-manage>' +
    '    <div class="panel">' +
    '      <div class="oc-head">' +
    '        <div><div class="kicker">File details</div><div class="t" data-mx="detail-title">File</div></div>' +
    '        <button type="button" class="icon-btn" data-oc-close aria-label="Close">' +
    I('<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>', ' width="14" height="14"') + '</button>' +
    '      </div>' +
    '      <form data-mx="edit-form" novalidate>' +
    '        <div class="oc-body">' +
    '          <img class="ph mx-detail-preview" data-mx="detail-preview" alt="">' +
    '          <div class="ph mx-detail-preview-file" data-mx="detail-preview-file"><span class="badge mx-detail-ext" data-mx="detail-ext">FILE</span></div>' +
    '          <div class="dim mx-detail-meta" data-mx="detail-meta"></div>' +
    '          <button type="button" class="btn btn-ghost btn-sm mx-detail-edit" data-mx="detail-edit">' +
    I('<path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/>') + '<span>Edit image</span></button>' +
    '          <label class="field"><span>Display name<span class="req"> *</span></span>' +
    '            <input class="input" name="displayName" data-mx="detail-name" placeholder="File name"></label>' +
    '          <label class="field"><span>Alt text</span>' +
    '            <input class="input" name="altText" data-mx="detail-alt" placeholder="Describe this image for SEO &amp; accessibility"></label>' +
    '          <label class="field"><span>Folder</span>' +
    '            <select class="select" name="folderId" data-mx="detail-folder"></select></label>' +
    '          <label class="field"><span>Public URL</span>' +
    '            <span class="mx-url-row"><input class="input" data-mx="detail-url" readonly>' +
    '            <button type="button" class="btn btn-ghost" data-mx="detail-copy">Copy</button></span></label>' +
    '        </div>' +
    '        <div class="oc-foot">' +
    '          <button class="btn" type="submit">Save changes</button>' +
    '          <button type="button" class="btn btn-ghost" data-oc-close>Cancel</button>' +
    '        </div>' +
    '      </form>' +
    '    </div>' +
    '  </div>' +
    '</div>';

  /* ------------------------------------------------------------------ component */

  function MediaExplorer(element, options) {
    this.id = ++instances;
    this.ns = '.mx' + this.id;
    this.opts = $.extend({
      mode: 'manage',
      accept: null,
      multiple: false,
      initialFolder: '',
      trashUrl: '/admin/media/trash',
      onSelect: null,
      onCancel: null
    }, options || {});
    this.pick = this.opts.mode === 'pick';
    this.pageSizeKey = 'kh.mediaExplorer.pageSize.' + this.opts.mode;
    this.state = {
      loaded: false,
      folders: [],
      folderMap: {},
      files: [],
      uploaders: [],
      trash: null,
      permissions: { create: false, edit: false, delete: false },
      current: '',
      page: 1,
      pageSize: DEFAULT_PAGE_SIZE,
      selected: {},       // file id -> true
      renameFolderId: null,
      detailFileId: null
    };
    this.state.pageSize = storedPageSize(this.pageSizeKey);
    this.$host = $(element);
    this.build();
    this.bind();
    this.load();
  }

  var P = MediaExplorer.prototype;

  P.$ = function (name) {
    return this.$root.find('[data-mx="' + name + '"]');
  };

  /* ---------------- build ---------------- */

  P.build = function () {
    this.$host.empty().append(TEMPLATE);
    this.$root = this.$host.children('.mx').first();
    this.$root.attr('data-mode', this.opts.mode);

    if (this.pick) {
      this.$root.find('[data-manage]').remove();
      this.$('trash-link').remove();
      this.$('trash-note').remove();
      this.$('hint').text(this.opts.multiple ? 'Click files to select them, then “Use selected”.' : 'Click a file to select it, double-click to use it right away.');
      this.$('pick-confirm').text(this.opts.multiple ? 'Use selected' : 'Use this file');
    } else {
      this.$('pick-foot').remove();
      this.$('hint').text('Right-click a folder for more options.');
      this.$('trash-link').attr('href', this.opts.trashUrl);
    }

    var images = this.opts.accept === 'image';
    if (images) {
      this.$('filter-kind').remove();
      this.$('file-input').attr('accept', IMAGE_TYPES.map(function (t) { return t[0]; }).join(','));
      this.$('dropzone-sub').text('Images (JPG, PNG, WEBP, GIF, SVG) — up to 10MB each');
    } else {
      this.$('file-input').attr('accept', IMAGE_TYPES.concat(FILE_TYPES).map(function (t) { return t[0]; }).join(','));
      this.$('dropzone-sub').text('Images (JPG, PNG, WEBP, GIF, SVG) or files (PDF, DOC(X), XLS(X), ZIP, TXT) — up to 10MB each');
    }

    var $type = this.$('filter-type').append($('<option>').val('').text('All types'));
    var addGroup = function (label, list) {
      var $g = $('<optgroup>').attr('label', label);
      list.forEach(function (t) { $('<option>').val(t[0]).text(t[1]).appendTo($g); });
      $type.append($g);
    };
    addGroup('Images', IMAGE_TYPES);
    if (!images) addGroup('Files', FILE_TYPES);

    this.$grid = this.$('grid');
    this.$menu = this.$('menu');
    this.$renameForm = this.$('rename-form');
    this.$folderModal = this.$('folder-modal');
    this.$detail = this.$('detail');
    var $size = this.$('page-size');
    PAGE_SIZES.forEach(function (n) { $('<option>').val(String(n)).text(String(n)).appendTo($size); });
    $size.val(String(this.state.pageSize));

    this.$grid.html('<div class="dim mx-loading">Loading…</div>');
  };

  /* ---------------- data ---------------- */

  P.load = function () {
    var self = this;
    var query = this.opts.accept === 'image' ? { kind: 'image' } : {};
    return $.getJSON(API + '/library', query)
      .done(function (data) {
        self.setLibrary(data);
        if (!self.state.loaded) {
          self.state.loaded = true;
          self.goToFolder(self.opts.initialFolder);
        } else {
          self.goToFolder(self.state.current, true);
        }
      })
      .fail(function (xhr) {
        self.$grid.empty();
        self.$('empty').text(errorOf(xhr, 'Could not load the media library.')).show();
      });
  };

  P.reload = function () {
    this.clearSelection();
    return this.load();
  };

  P.setLibrary = function (data) {
    this.state.files = data.files || [];
    this.state.uploaders = data.uploaders || [];
    this.state.trash = data.trash || null;
    this.state.permissions = $.extend({ create: false, edit: false, delete: false }, data.permissions || {});
    this.setFolders(data.folders || []);
    this.applyPermissions();
    this.renderUploaders();
    this.renderTrash();
  };

  P.setFolders = function (folders) {
    var map = {};
    folders.forEach(function (f) {
      f.id = String(f.id);
      f.parentId = norm(f.parentId);
      f.name = String(f.name);
      map[f.id] = f;
    });
    this.state.folders = folders;
    this.state.folderMap = map;
    if (this.state.current !== '' && !map[this.state.current]) {
      this.state.current = '';
    }
    this.renderSidebar();
    this.renderFolderSelects();
  };

  P.applyPermissions = function () {
    var perms = this.state.permissions;
    if (!perms.create) {
      this.$('new-folder').remove();
      this.$('upload').remove();
      this.$('dropzone').remove();
    }
    this.$root.find('[data-perm]').each(function () {
      if (!perms[$(this).attr('data-perm')]) $(this).remove();
    });
  };

  P.fileById = function (id) {
    id = String(id);
    for (var i = 0; i < this.state.files.length; i++) {
      if (String(this.state.files[i].id) === id) return this.state.files[i];
    }
    return null;
  };

  P.ancestors = function (folderId) {
    var map = this.state.folderMap;
    var node = map[norm(folderId)];
    if (!node) return [];
    return segments(node.path).map(function (id) { return map[id] || { id: id, name: 'Folder' }; });
  };

  P.isSelfOrDescendant = function (folderId, candidateId) {
    var map = this.state.folderMap;
    if (norm(folderId) === norm(candidateId)) return true;
    var base = map[norm(folderId)] && map[norm(folderId)].path;
    var other = map[norm(candidateId)] && map[norm(candidateId)].path;
    return !!base && !!other && other.indexOf(base) === 0;
  };

  /* ---------------- navigation ---------------- */

  P.goToFolder = function (folderId, keepSelection) {
    var id = norm(folderId);
    this.state.current = this.state.folderMap[id] ? id : '';
    if (!keepSelection) this.clearSelection(true);
    this.renderSidebar();
    this.renderBreadcrumb();
    var name = this.state.current === '' ? '' : this.state.folderMap[this.state.current].name;
    this.$('folder-title').text(name ? 'New folder in ' + name : 'New folder');
    this.$('folder-location').text(name ? 'Creates the folder inside "' + name + '".' : 'Creates the folder at Home.');
    this.resetAndRender();
  };

  /* ---------------- rendering ---------------- */

  P.renderSidebar = function () {
    var self = this;
    var $list = this.$('folders').empty();
    // Only top-level folders are shown in the sidebar; inside a subfolder its top-level ancestor is highlighted.
    var trail = this.ancestors(this.state.current);
    var highlighted = trail.length ? String(trail[0].id) : '';
    $('<li class="folder-item" data-depth="0">').attr('data-folder', '').text('Home')
      .attr('aria-current', highlighted === '' ? 'true' : 'false').appendTo($list);
    this.state.folders.forEach(function (f) {
      if (f.depth !== 0) return;
      $('<li class="folder-item" data-depth="0">').attr('data-folder', f.id).attr('data-name', f.name).text(f.name)
        .attr('aria-current', highlighted === f.id ? 'true' : 'false').appendTo($list);
    });
    return self;
  };

  P.renderFolderSelects = function () {
    var folders = this.state.folders;
    var fill = function ($select, homeLabel, prefix) {
      if (!$select.length) return;
      var value = $select.val();
      $select.empty().append($('<option>').val('').text(homeLabel));
      folders.forEach(function (f) {
        $('<option>').val(f.id).text(prefix + new Array(f.depth + 1).join('— ') + f.name).appendTo($select);
      });
      if (value !== null && $select.find('option').filter(function () { return this.value === value; }).length) $select.val(value);
    };
    fill(this.$('sel-move-folder'), 'Move to: Home', 'Move to: ');
    fill(this.$('detail-folder'), 'Home (unfiled)', '');
  };

  P.renderUploaders = function () {
    var $sel = this.$('filter-uploader');
    var value = $sel.val();
    $sel.empty().append($('<option>').val('').text('All uploaders'));
    this.state.uploaders.forEach(function (u) { $('<option>').val(String(u.id)).text(u.name).appendTo($sel); });
    if (value) $sel.val(value);
  };

  P.renderTrash = function () {
    var t = this.state.trash;
    if (!t) return;
    var items = (t.fileCount || 0) + (t.folderCount || 0);
    this.$('trash-count').text(items > 0 ? String(items) : '');
    this.$('trash-note').text(t.totalBytes > 0
      ? 'Trash holds ' + (t.totalBytes / 1048576).toFixed(2) + ' MB — still counted against quota.'
      : '');
  };

  P.renderBreadcrumb = function () {
    var self = this;
    var $bc = this.$('breadcrumb').empty();
    $('<button type="button">').text('Home').on('click', function () { self.goToFolder(''); }).appendTo($bc);
    var trail = this.ancestors(this.state.current);
    trail.forEach(function (node, i) {
      $('<span class="sep">').text('/').appendTo($bc);
      if (i === trail.length - 1) {
        $('<span class="current">').text(node.name).appendTo($bc);
      } else {
        $('<button type="button">').text(node.name).on('click', function () { self.goToFolder(node.id); }).appendTo($bc);
      }
    });
  };

  P.filters = function () {
    return {
      q: $.trim(this.$('search').val() || '').toLowerCase(),
      kind: this.$('filter-kind').val() || '',
      type: this.$('filter-type').val() || '',
      usage: this.$('filter-usage').val() || '',
      uploader: this.$('filter-uploader').val() || '',
      from: this.$('filter-from').val() || '',
      to: this.$('filter-to').val() || '',
      sort: this.$('sort').val() || 'newest'
    };
  };

  P.visibleFolders = function (f) {
    var current = this.state.current;
    return this.state.folders.filter(function (folder) {
      if (folder.parentId !== current) return false;
      return !f.q || folder.name.toLowerCase().indexOf(f.q) > -1;
    });
  };

  P.visibleFiles = function (f) {
    var current = this.state.current;
    var list = this.state.files.filter(function (file) {
      if (norm(file.folderId) !== current) return false;
      if (f.q && String(file.name || '').toLowerCase().indexOf(f.q) === -1) return false;
      if (f.kind && file.kind !== f.kind) return false;
      if (f.type && file.contentType !== f.type) return false;
      if (f.usage === 'used' && !file.used) return false;
      if (f.usage === 'unused' && file.used) return false;
      if (f.uploader && String(file.uploaderId) !== f.uploader) return false;
      var day = String(file.createdAt || '').slice(0, 10);
      if (f.from && day && day < f.from) return false;
      if (f.to && day && day > f.to) return false;
      return true;
    });
    list.sort(function (a, b) {
      switch (f.sort) {
        case 'oldest': return String(a.createdAt).localeCompare(String(b.createdAt));
        case 'largest': return (b.sizeBytes || 0) - (a.sizeBytes || 0);
        case 'smallest': return (a.sizeBytes || 0) - (b.sizeBytes || 0);
        case 'name': return String(a.name).localeCompare(String(b.name));
        default: return String(b.createdAt).localeCompare(String(a.createdAt));
      }
    });
    return list;
  };

  P.folderTile = function (folder) {
    var $tile = $('<div class="icon-tile folder-tile">').attr('data-folder-id', folder.id).attr('data-name', folder.name);
    $('<div class="icon-wrap">').html(FOLDER_TILE).appendTo($tile);
    $('<span class="name">').text(folder.name).appendTo($tile);
    return $tile;
  };

  P.fileTile = function (file) {
    var selected = !!this.state.selected[file.id];
    var $tile = $('<div class="icon-tile file-tile">').attr('data-id', file.id).attr('data-name', file.name)
      .toggleClass('selected', selected);
    $('<input class="checkbox tile-check" type="checkbox">').val(file.id).prop('checked', selected)
      .attr('aria-label', 'Select ' + file.name).appendTo($tile);
    var $wrap = $('<div class="icon-wrap">').appendTo($tile);
    if (file.kind === 'image') {
      $('<img loading="lazy" alt="">').attr('src', versioned(file.thumbUrl || file.url, file)).appendTo($wrap);
      if (window.lightbox) {
        $('<button type="button" class="tile-preview">').attr({ title: 'Preview', 'aria-label': 'Preview ' + file.name })
          .html(I('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M11 8v6"/><path d="M8 11h6"/>', ' width="13" height="13"')).appendTo($tile);
      }
    } else {
      $wrap.html(FILE_TILE);
    }
    $('<span class="name">').text(file.name).appendTo($tile);
    $('<span class="sub">').text(file.kind === 'image' ? kb(file.sizeBytes) : file.ext).appendTo($tile);
    if (file.used) $('<span class="badge-mini">').text('Used').appendTo($tile);
    return $tile;
  };

  P.render = function () {
    var self = this;
    var f = this.filters();
    var folders = this.visibleFolders(f);
    var files = this.visibleFiles(f);
    var size = this.state.pageSize;
    var pageCount = Math.max(1, Math.ceil(files.length / size));
    // Keep the page valid after deletes/filters shrink the list.
    this.state.page = Math.min(Math.max(1, this.state.page), pageCount);
    var start = (this.state.page - 1) * size;
    var slice = files.slice(start, start + size);
    // Folders are listed once, on the first page, like a file explorer.
    if (this.state.page > 1) folders = [];

    this.$grid.empty();
    folders.forEach(function (folder) { self.$grid.append(self.folderTile(folder)); });
    slice.forEach(function (file) { self.$grid.append(self.fileTile(file)); });

    var $empty = this.$('empty');
    if (!folders.length && !files.length) {
      $empty.text(this.state.files.length + this.state.folders.length === 0
        ? (this.opts.accept === 'image' ? 'No images uploaded yet.' : 'No files uploaded yet.')
        : 'Nothing here matches these filters.').show();
    } else {
      $empty.hide();
    }

    // Filters stay usable during a selection: a selected file that drops out of view is deselected,
    // so bulk actions and "Use selected" only ever act on files the user can see.
    var visible = {};
    slice.forEach(function (file) { visible[file.id] = true; });
    Object.keys(this.state.selected).forEach(function (id) { if (!visible[id]) delete self.state.selected[id]; });

    this.renderPager(files.length, pageCount, start, slice.length);
    var total = this.state.files.length;
    this.$('count').text(total + (total === 1 ? ' file' : ' files'));
    this.syncSelection();
  };

  P.renderPager = function (total, pageCount, start, shown) {
    var $pager = this.$('pager').toggle(total > 0);
    if (!total) return $pager;
    this.$('page-info').text('Showing ' + (start + 1) + '–' + (start + shown) + ' of ' + total + (total === 1 ? ' file' : ' files'));

    var page = this.state.page;
    var $pages = this.$('pages').empty();
    var button = function (label, target, extra) {
      var $b = $('<button type="button">').text(label).attr('data-page', target);
      if (extra.current) $b.attr('aria-current', 'page');
      if (extra.disabled) $b.prop('disabled', true);
      if (extra.title) $b.attr({ title: extra.title, 'aria-label': extra.title });
      return $b.appendTo($pages);
    };
    button('‹', page - 1, { disabled: page <= 1, title: 'Previous page' });
    pageList(page, pageCount).forEach(function (n) {
      if (n === '…') $('<span class="mx-gap dim">').text('…').appendTo($pages);
      else button(String(n), n, { current: n === page, title: 'Page ' + n });
    });
    button('›', page + 1, { disabled: page >= pageCount, title: 'Next page' });
    return $pager;
  };

  /** Back to page 1: filters, navigation, uploads and page-size changes all start over. */
  P.resetAndRender = function () {
    this.state.page = 1;
    this.render();
    this.$('scroll').scrollTop(0);
  };

  P.goToPage = function (page) {
    if (!page || page === this.state.page) return;
    this.state.page = page;
    this.render();            // render() clamps the page and deselects files that left the view
    this.$('scroll').scrollTop(0);
  };

  P.setPageSize = function (size) {
    size = Number(size);
    if (PAGE_SIZES.indexOf(size) === -1) return;
    this.state.pageSize = size;
    try { window.localStorage.setItem(this.pageSizeKey, String(size)); } catch (e) { /* storage blocked: size just won't be remembered */ }
    this.resetAndRender();
  };

  /* ---------------- selection ---------------- */

  P.selectedIds = function () {
    return Object.keys(this.state.selected);
  };

  P.selectedFiles = function () {
    var self = this;
    return this.selectedIds().map(function (id) { return self.fileById(id); }).filter(Boolean);
  };

  P.setSelected = function (id, on) {
    id = String(id);
    if (this.pick && !this.opts.multiple) this.state.selected = {};
    if (on) this.state.selected[id] = true; else delete this.state.selected[id];
    var sel = this.state.selected;
    this.$grid.find('.file-tile').each(function () {
      var isOn = !!sel[$(this).attr('data-id')];
      $(this).toggleClass('selected', isOn).find('.tile-check').prop('checked', isOn);
    });
    this.syncSelection();
  };

  P.clearSelection = function (silent) {
    this.state.selected = {};
    if (!silent) {
      this.$grid.find('.file-tile').removeClass('selected').find('.tile-check').prop('checked', false);
    }
    this.syncSelection();
  };

  P.syncSelection = function () {
    var n = this.selectedIds().length;
    this.$('selection-bar').toggleClass('show', n > 0);
    this.$('selection-count').text(n + (n === 1 ? ' item selected' : ' items selected'));
    if (this.pick) {
      this.$('pick-confirm').prop('disabled', n === 0);
      this.$('pick-summary').text(n === 0 ? 'Nothing selected' : n + (n === 1 ? ' file selected' : ' files selected'));
    }
  };

  P.choose = function (files) {
    if (!files.length) return;
    if (typeof this.opts.onSelect === 'function') {
      this.opts.onSelect(files.map(function (f) { return $.extend({}, f); }));
    }
  };

  /* ---------------- upload ---------------- */

  P.upload = function (fileList) {
    var self = this;
    if (!fileList || !fileList.length || !this.state.permissions.create) return;
    var fd = new FormData();
    fd.append('_csrf', csrfToken());
    if (this.state.current) fd.append('folderId', this.state.current);
    $.each(fileList, function (_, file) { fd.append('files', file); });

    var $label = this.$('upload-label').text('Uploading…');
    this.$('upload').prop('disabled', true);
    $.ajax({
      url: API + '/files',
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: { 'X-CSRF-TOKEN': csrfToken() },
      dataType: 'json'
    }).done(function (res) {
      var added = (res.files || []).filter(function (f) { return self.opts.accept !== 'image' || f.kind === 'image'; });
      self.state.files = added.concat(self.state.files);
      added.forEach(function (f) {
        if (f.uploaderId && !self.state.uploaders.some(function (u) { return String(u.id) === String(f.uploaderId); })) {
          self.state.uploaders.push({ id: f.uploaderId, name: f.uploaderName });
        }
      });
      self.renderUploaders();
      self.resetAndRender();
      if (self.pick && added.length) {
        // In a picker, what you just uploaded is almost always what you want to use.
        (self.opts.multiple ? added : added.slice(0, 1)).forEach(function (f) { self.setSelected(f.id, true); });
      }
      notify(res.message);
    }).fail(function (xhr) {
      notify(errorOf(xhr, 'Upload failed. Please try again.'));
    }).always(function () {
      $label.text('Upload');
      self.$('upload').prop('disabled', false);
      self.$('file-input').val('');
    });
  };

  /* ---------------- menus & popovers ---------------- */

  P.closeMenus = function () {
    // No .empty(): the menu fades out, and openMenu() rebuilds it on the next open.
    this.$menu.removeClass('open');
    this.$renameForm.removeClass('open');
    var v = this.$renameForm.data('validator');
    if (v) v.resetForm();
  };

  P.position = function ($el, x, y) {
    // Closed menus stay laid out (they fade via opacity/visibility), so they can be measured as-is.
    // Never toggle .open or write an inline visibility here: that races the fade transition.
    var w = $el.outerWidth();
    var h = $el.outerHeight();
    $el.css({
      left: Math.max(8, Math.min(x, $(window).width() - w - 8)) + 'px',
      top: Math.max(8, Math.min(y, $(window).height() - h - 8)) + 'px'
    });
  };

  P.openMenu = function (x, y, build) {
    this.$menu.empty();
    build(this.$menu);
    if (!this.$menu.children('button').length) return;
    this.position(this.$menu, x, y);
    this.$menu.addClass('open');
  };

  P.menuItem = function ($menu, icon, label, danger, handler) {
    var self = this;
    $('<button type="button">').toggleClass('danger', !!danger).append(ICONS[icon] || '')
      .append($('<span>').text(label))
      .on('click', function () { self.closeMenus(); handler(); })
      .appendTo($menu);
  };

  P.folderMenu = function ($menu, folder, event) {
    var self = this;
    var perms = this.state.permissions;
    this.menuItem($menu, 'open', 'Open', false, function () { self.goToFolder(folder.id); });
    if (perms.create) {
      this.menuItem($menu, 'folder', 'New subfolder', false, function () {
        self.goToFolder(folder.id);
        self.openFolderModal();
      });
    }
    if (this.pick) return;
    if (perms.edit) {
      this.menuItem($menu, 'rename', 'Rename', false, function () { self.openRename(folder, event.clientX, event.clientY); });
    }
    this.menuItem($menu, 'info', 'View info', false, function () {
      var count = self.state.files.filter(function (f) { return norm(f.folderId) === folder.id; }).length;
      notify(folder.name + ' — ' + count + (count === 1 ? ' file' : ' files') + ' — created ' + (folder.createdAt || 'unknown date'));
    });
    if (perms.edit) {
      $('<hr>').appendTo($menu);
      $('<div class="cm-label">').text('Move to').appendTo($menu);
      if (folder.parentId !== '') {
        this.menuItem($menu, 'move', 'Home', false, function () { self.moveFolder(folder.id, ''); });
      }
      this.state.folders.forEach(function (target) {
        // Not into itself or its own subtree (that would cut the branch loose), nor where it already is.
        if (self.isSelfOrDescendant(folder.id, target.id) || target.id === folder.parentId) return;
        self.menuItem($menu, 'move', new Array(target.depth + 1).join('— ') + target.name, false, function () {
          self.moveFolder(folder.id, target.id);
        });
      });
    }
    if (perms.delete) {
      $('<hr>').appendTo($menu);
      this.menuItem($menu, 'delete', 'Delete', true, function () { self.deleteFolder(folder); });
    }
  };

  P.fileMenu = function ($menu, file) {
    var self = this;
    var perms = this.state.permissions;
    if (this.pick) {
      if (this.canPreview(file)) {
        this.menuItem($menu, 'preview', 'Preview', false, function () { self.previewImage(file); });
      }
      this.menuItem($menu, 'select', this.opts.multiple ? 'Select' : 'Use this file', false, function () {
        if (self.opts.multiple) self.setSelected(file.id, true); else self.choose([file]);
      });
      return;
    }
    this.menuItem($menu, 'open', 'Open', false, function () { self.openDetail(file); });
    if (this.canPreview(file)) {
      this.menuItem($menu, 'preview', 'Preview', false, function () { self.previewImage(file); });
    }
    if (this.canEditImage(file)) {
      this.menuItem($menu, 'edit', 'Edit image', false, function () { self.editImage(file); });
    }
    if (perms.edit) {
      this.menuItem($menu, 'rename', 'Rename', false, function () { self.openDetail(file, true); });
      $('<hr>').appendTo($menu);
      $('<div class="cm-label">').text('Move to').appendTo($menu);
      this.menuItem($menu, 'move', 'Home', false, function () { self.moveFiles([file.id], ''); });
      this.state.folders.forEach(function (target) {
        self.menuItem($menu, 'move', new Array(target.depth + 1).join('— ') + target.name, false, function () {
          self.moveFiles([file.id], target.id);
        });
      });
    }
    $('<hr>').appendTo($menu);
    this.menuItem($menu, 'download', 'Download', false, function () { self.download([file]); });
    if (perms.delete) {
      this.menuItem($menu, 'delete', 'Delete', true, function () { self.deleteFiles([file]); });
    }
  };

  /* ---------------- folder modal / rename ---------------- */

  P.openFolderModal = function () {
    var self = this;
    if (!this.state.permissions.create) return;
    this.closeMenus();
    var v = this.$('folder-form').data('validator');
    if (v) v.resetForm();
    this.$('folder-input').val('').removeClass('error');
    this.$folderModal.addClass('open');
    setTimeout(function () { self.$('folder-input').trigger('focus'); }, 0);
  };

  P.closeFolderModal = function () {
    this.$folderModal.removeClass('open');
  };

  P.openRename = function (folder, x, y) {
    this.state.renameFolderId = folder.id;
    this.$('rename-input').val(folder.name);
    this.position(this.$renameForm, x, y);
    this.$renameForm.addClass('open');
    this.$('rename-input').trigger('focus').trigger('select');
  };

  /* ---------------- detail panel (manage) ---------------- */

  P.openDetail = function (file, focusName) {
    var self = this;
    if (this.pick || !this.$detail.length) return;
    this.state.detailFileId = String(file.id);
    var v = this.$('edit-form').data('validator');
    if (v) v.resetForm();
    this.$('detail-title').text(file.name);
    this.$('detail-name').val(file.name).removeClass('error');
    this.$('detail-alt').val(file.altText || '');
    this.renderFolderSelects();
    this.$('detail-folder').val(norm(file.folderId));
    this.$('detail-url').val(file.url);
    if (file.kind === 'image') {
      this.$('detail-preview').attr('src', versioned(file.thumbUrl || file.url, file)).show();
      this.$('detail-preview-file').hide();
    } else {
      this.$('detail-preview').hide();
      this.$('detail-ext').text(file.ext || 'FILE');
      this.$('detail-preview-file').css('display', 'flex');
    }
    var meta = [];
    if (file.kind === 'image' && file.width && file.height) meta.push(file.width + ' × ' + file.height + ' px');
    meta.push(kb(file.sizeBytes) + (file.optimized && file.originalSizeBytes ? ' (was ' + kb(file.originalSizeBytes) + ' before optimization)' : ''));
    if (file.kind === 'image') meta.push(file.optimized ? 'Optimized via TinyPNG' : 'Not optimized');
    if (file.createdAt) {
      meta.push('Uploaded ' + String(file.createdAt).replace('T', ' ').slice(0, 16) + (file.uploaderName ? ' by ' + file.uploaderName : ''));
    }
    this.$('detail-edit').toggle(this.canEditImage(file));
    var $meta = this.$('detail-meta').empty();
    meta.forEach(function (line) { $('<span>').text(line).appendTo($meta); });
    this.$detail.addClass('open');
    if (focusName) setTimeout(function () { self.$('detail-name').trigger('focus').trigger('select'); }, 50);
  };

  /* ---------------- image editor (scripts/media/image-editor.js, optional) ---------------- */

  P.canEditImage = function (file) {
    var perms = this.state.permissions;
    return !this.pick && !!window.MediaImageEditor && window.MediaImageEditor.canEdit(file) && !!(perms.edit || perms.create);
  };

  P.editImage = function (file) {
    var self = this;
    if (!this.canEditImage(file)) return;
    this.closeMenus();
    window.MediaImageEditor.open(file, {
      canReplace: !!this.state.permissions.edit,
      canCopy: !!this.state.permissions.create,
      onReplaced: function (updated) {
        self.replaceFiles([updated]);
        self.render();
        if (self.$detail.hasClass('open') && String(self.state.detailFileId) === String(updated.id)) self.openDetail(updated);
      },
      onCopied: function (created) {
        self.state.files.unshift(created);
        self.resetAndRender();
      }
    });
  };

  /* ---------------- image preview (Lightbox2, loaded by the admin layout) ---------------- */

  P.canPreview = function (file) {
    return !!(window.lightbox && file && file.kind === 'image');
  };

  /**
   * Opens Lightbox2 on `file`. The album is every image in the current folder view (same filters and
   * sort as the grid, across all pages), so the arrows walk through what the user is looking at.
   * Lightbox2 builds albums from a[data-lightbox] links, so hidden links are rendered per instance.
   */
  P.previewImage = function (file) {
    if (!this.canPreview(file)) return;
    var self = this;
    this.closeMenus();
    if (!this.previewGroup) this.previewGroup = 'mx-preview-' + Math.random().toString(36).slice(2, 10);

    var images = this.visibleFiles(this.filters()).filter(function (f) { return f.kind === 'image'; });
    if (!images.some(function (f) { return String(f.id) === String(file.id); })) images = [file];

    var $links = this.$root.children('[data-mx="preview-links"]');
    if (!$links.length) $links = $('<div data-mx="preview-links" hidden>').appendTo(this.$root);
    $links.empty();

    var $start = null;
    images.forEach(function (f) {
      var caption = f.name + (f.width && f.height ? ' · ' + f.width + ' × ' + f.height + ' px' : '') + ' · ' + kb(f.sizeBytes);
      var $a = $('<a>').attr({
        // Lightbox2 detects SVG by the URL's extension, so SVGs keep the bare URL.
        href: /svg/i.test(f.contentType || '') ? f.url : versioned(f.url, f),
        'data-lightbox': self.previewGroup,
        'data-title': caption,
        'data-alt': f.altText || f.name
      }).appendTo($links);
      if (String(f.id) === String(file.id)) $start = $a;
    });

    window.lightbox.option({
      sanitizeTitle: true, // captions are file names: never insert them as HTML
      wrapAround: images.length > 1,
      albumLabel: 'Image %1 of %2',
      fadeDuration: 200,
      imageFadeDuration: 200,
      resizeDuration: 250
    });
    window.lightbox.start($start);
  };

  /** Space previews the first selected image, unless the user is typing or another overlay is open. */
  P.previewFromKeyboard = function (e) {
    if (!window.lightbox || $(e.target).closest('input, textarea, select, button, [contenteditable="true"]').length) return false;
    if (!this.$root.is(':visible') || $('#lightbox').is(':visible') || $('.ie-modal.open').length || this.$folderModal.hasClass('open')) return false;
    var image = this.selectedFiles().filter(function (f) { return f.kind === 'image'; })[0];
    if (!image) return false;
    e.preventDefault();
    this.previewImage(image);
    return true;
  };

  /* ---------------- API actions ---------------- */

  P.replaceFiles = function (files) {
    var self = this;
    (files || []).forEach(function (updated) {
      for (var i = 0; i < self.state.files.length; i++) {
        if (String(self.state.files[i].id) === String(updated.id)) {
          self.state.files[i] = updated;
          return;
        }
      }
    });
  };

  P.moveFiles = function (ids, folderId) {
    var self = this;
    if (!ids.length) return notify('No files selected');
    var data = { ids: ids };
    if (folderId) data.folderId = folderId;
    post(API + '/files/move', data)
      .done(function (res) { self.replaceFiles(res.files); self.clearSelection(true); self.render(); notify(res.message); })
      .fail(function (xhr) { notify(errorOf(xhr, 'Could not move the files.')); });
  };

  P.moveFolder = function (folderId, parentId) {
    var self = this;
    var data = {};
    if (parentId) data.parentId = parentId;
    post(API + '/folders/' + folderId + '/move', data)
      .done(function (res) { self.setFolders(res.folders); self.goToFolder(self.state.current, true); notify(res.message); })
      .fail(function (xhr) { notify(errorOf(xhr, 'Could not move the folder.')); });
  };

  P.removeDeleted = function (mediaIds, folderIds) {
    var self = this;
    var gone = {};
    (mediaIds || []).forEach(function (id) { gone['f' + id] = true; });
    (folderIds || []).forEach(function (id) { gone['d' + id] = true; });

    this.state.files = this.state.files.filter(function (f) { return !gone['f' + f.id]; });
    (mediaIds || []).forEach(function (id) { delete self.state.selected[String(id)]; });
    var remaining = this.state.folders.filter(function (f) { return !gone['d' + f.id]; });

    var $tiles = this.$grid.find('.file-tile').filter(function () { return gone['f' + $(this).attr('data-id')]; })
      .add(this.$grid.find('.folder-tile').filter(function () { return gone['d' + $(this).attr('data-folder-id')]; }));
    $tiles.fadeOut(200);
    setTimeout(function () {
      var inside = self.state.current !== '' && gone['d' + self.state.current];
      self.setFolders(remaining);
      if (inside) self.goToFolder('');   // we were standing inside what was just deleted
      else { self.renderBreadcrumb(); self.render(); }
    }, 210);
  };

  P.deleteFiles = function (files) {
    var self = this;
    if (!files.length || !window.khDialog) return;
    var single = files.length === 1;
    window.khDialog(single ? 'file "' + files[0].name + '"' : files.length + ' selected files', function () {
      var request = single
        ? post(API + '/files/' + files[0].id + '/delete')
        : post(API + '/files/delete', { ids: files.map(function (f) { return f.id; }) });
      request
        .done(function (res) { self.removeDeleted(res.mediaIds, []); notify(res.message); })
        .fail(function (xhr) { notify(errorOf(xhr, 'Delete failed. Please try again.')); });
    }, single
      ? 'The file moves to the trash. You can restore it from Trash until it is deleted permanently.'
      : 'The files move to the trash. You can restore them from Trash until they are deleted permanently.');
  };

  P.deleteFolder = function (folder) {
    var self = this;
    if (!window.khDialog) return;
    window.khDialog('folder "' + folder.name + '" and everything inside it', function () {
      post(API + '/folders/' + folder.id + '/delete')
        .done(function (res) { self.removeDeleted(res.mediaIds, res.folderIds); notify(res.message); })
        .fail(function (xhr) { notify(errorOf(xhr, 'Delete failed. Please try again.')); });
    }, 'The folder, its subfolders and all their files move to the trash. You can restore them from Trash.');
  };

  P.download = function (files) {
    files.forEach(function (file, i) {
      setTimeout(function () {
        var a = document.createElement('a');
        a.href = file.url;
        a.download = file.name || '';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      }, i * 150);
    });
  };

  /* ---------------- events ---------------- */

  P.bind = function () {
    var self = this;
    var ns = this.ns;
    var $root = this.$root;

    // toolbar / upload
    this.$('new-folder').on('click', function () { self.openFolderModal(); });
    this.$('upload').on('click', function () { self.$('file-input').trigger('click'); });
    this.$('file-input').on('change', function () { self.upload(this.files); });
    this.$('refresh').on('click', function () { self.reload(); });
    var $drop = this.$('dropzone');
    $drop.on('click', function () { self.$('file-input').trigger('click'); });
    $drop.on('dragover', function (e) { e.preventDefault(); $drop.addClass('drag'); });
    $drop.on('dragleave', function (e) { e.preventDefault(); $drop.removeClass('drag'); });
    $drop.on('drop', function (e) {
      e.preventDefault();
      $drop.removeClass('drag');
      var dt = e.originalEvent.dataTransfer;
      if (dt && dt.files && dt.files.length) self.upload(dt.files);
    });

    // sidebar
    this.$('folders').on('click', '.folder-item', function () { self.goToFolder($(this).attr('data-folder')); });
    $root.find('.media-sidebar').on('contextmenu', function (e) {
      e.preventDefault();
      var $item = $(e.target).closest('.folder-item');
      var folder = $item.length ? self.state.folderMap[norm($item.attr('data-folder'))] : null;
      self.openMenu(e.clientX, e.clientY, function ($menu) {
        if (folder) self.folderMenu($menu, folder, e);
        else if (self.state.permissions.create) self.menuItem($menu, 'folder', 'New folder', false, function () { self.openFolderModal(); });
      });
    });

    // grid
    this.$grid.on('click', '.folder-tile', function () { self.goToFolder($(this).attr('data-folder-id')); });
    this.$grid.on('click', '.tile-preview', function (e) {
      e.stopPropagation(); // don't toggle the tile's selection
      var file = self.fileById($(this).closest('.file-tile').attr('data-id'));
      if (file) self.previewImage(file);
    });
    this.$grid.on('click', '.file-tile', function (e) {
      if ($(e.target).is('.tile-check')) return;
      var id = $(this).attr('data-id');
      self.setSelected(id, !self.state.selected[id]);
    });
    this.$grid.on('change', '.tile-check', function () { self.setSelected(this.value, this.checked); });
    this.$grid.on('dblclick', '.file-tile', function (e) {
      if ($(e.target).closest('.tile-preview').length) return;
      var file = self.fileById($(this).attr('data-id'));
      if (!file) return;
      if (self.pick) self.choose([file]); else self.openDetail(file);
    });
    this.$grid.on('contextmenu', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $folderTile = $(e.target).closest('.folder-tile');
      var $fileTile = $(e.target).closest('.file-tile');
      self.openMenu(e.clientX, e.clientY, function ($menu) {
        if ($folderTile.length) {
          var folder = self.state.folderMap[norm($folderTile.attr('data-folder-id'))];
          if (folder) self.folderMenu($menu, folder, e);
        } else if ($fileTile.length) {
          var file = self.fileById($fileTile.attr('data-id'));
          if (file) self.fileMenu($menu, file);
        } else if (self.state.permissions.create) {
          self.menuItem($menu, 'folder', 'New folder', false, function () { self.openFolderModal(); });
        }
      });
    });

    // filters & paging
    this.$('search').on('input', function () { self.resetAndRender(); });
    $root.find('.explorer-filters select, .explorer-filters .mx-f-date').not('[data-mx="page-size"]').on('change', function () { self.resetAndRender(); });
    this.$('pages').on('click', 'button[data-page]', function () { self.goToPage(Number($(this).attr('data-page'))); });
    this.$('page-size').on('change', function () { self.setPageSize(this.value); });
    if (window.flatpickr) {
      var $from = this.$('filter-from');
      var $to = this.$('filter-to');
      this.toPicker = window.flatpickr($to[0], { dateFormat: 'Y-m-d', allowInput: true, disableMobile: true });
      this.fromPicker = window.flatpickr($from[0], {
        dateFormat: 'Y-m-d',
        allowInput: true,
        disableMobile: true,
        onChange: function (dates, dateStr) { self.toPicker.set('minDate', dateStr || null); } // "to" never before "from"
      });
    } else {
      this.$('filter-from').attr('type', 'date');
      this.$('filter-to').attr('type', 'date');
    }

    // selection bar
    this.$('sel-clear').on('click', function () { self.clearSelection(); });
    this.$('sel-rename').on('click', function () {
      var files = self.selectedFiles();
      if (files.length !== 1) return notify('Select exactly one file to rename.');
      self.openDetail(files[0], true);
    });
    this.$('sel-move').on('click', function () { self.moveFiles(self.selectedIds(), self.$('sel-move-folder').val()); });
    this.$('sel-download').on('click', function () {
      var files = self.selectedFiles();
      if (!files.length) return notify('No files selected');
      self.download(files);
    });
    this.$('sel-delete').on('click', function () {
      var files = self.selectedFiles();
      if (!files.length) return notify('No files selected');
      self.deleteFiles(files);
    });

    // pick footer
    this.$('pick-confirm').on('click', function () { self.choose(self.selectedFiles()); });
    this.$('pick-cancel').on('click', function () {
      if (typeof self.opts.onCancel === 'function') self.opts.onCancel();
    });

    // folder modal
    this.$('folder-cancel').on('click', function () { self.closeFolderModal(); });
    this.$folderModal.on('mousedown', function (e) { if (e.target === this) self.closeFolderModal(); });

    // rename popover
    this.$('rename-cancel').on('click', function () { self.closeMenus(); });

    // detail panel
    this.$('detail-preview').on('click', function () {
      var file = self.fileById(self.state.detailFileId);
      if (file) self.previewImage(file);
    });
    this.$('detail-edit').on('click', function () {
      var id = String(self.state.detailFileId);
      var file = self.state.files.filter(function (f) { return String(f.id) === id; })[0];
      if (file) self.editImage(file);
    });
    this.$('detail-copy').on('click', function () {
      var url = self.$('detail-url').val();
      var done = function () { notify('URL copied'); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(url).then(done, done);
      else { self.$('detail-url')[0].select(); try { document.execCommand('copy'); } catch (err) { /* ignore */ } done(); }
    });

    this.bindForms();

    // document-level: close menus on outside click / Escape (namespaced per instance)
    $(document).on('click' + ns, function (e) {
      if (!$(e.target).closest(self.$menu.add(self.$renameForm)).length) self.closeMenus();
    });
    $(document).on('keydown' + ns, function (e) {
      if ((e.key === ' ' || e.code === 'Space') && self.previewFromKeyboard(e)) return;
      if (e.key !== 'Escape') return;
      self.closeMenus();
      if (self.$folderModal.hasClass('open')) self.closeFolderModal();
    });
    $(document).on('contextmenu' + ns, function (e) {
      if (!$(e.target).closest($root.find('.media-sidebar, [data-mx="grid"]')).length) self.closeMenus();
    });
  };

  P.bindForms = function () {
    var self = this;
    var ui = {
      errorElement: 'span',
      errorClass: 'err show',
      highlight: function (el) { $(el).addClass('error'); },
      unhighlight: function (el) { $(el).removeClass('error'); },
      errorPlacement: function (error, element) { error.insertAfter(element); }
    };
    var trim = function (v) { return $.trim(v); };
    var nameRules = { required: true, normalizer: trim, minlength: 2, maxlength: 150 };
    var nameMessages = {
      required: 'Folder name is required.',
      minlength: 'Folder name must have at least 2 characters.',
      maxlength: 'Folder name must be at most 150 characters.'
    };

    var validate = function ($form, config, submit) {
      if ($.fn.validate) {
        $form.validate($.extend({}, ui, config, { submitHandler: function () { submit(); return false; } }));
      } else {
        $form.on('submit', function (e) { e.preventDefault(); submit(); });
      }
    };

    validate(this.$('folder-form'), { rules: { name: nameRules }, messages: { name: nameMessages } }, function () {
      var data = { name: $.trim(self.$('folder-input').val()) };
      if (self.state.current) data.parentId = self.state.current;
      post(API + '/folders', data)
        .done(function (res) { self.closeFolderModal(); self.setFolders(res.folders); self.render(); notify(res.message); })
        .fail(function (xhr) { notify(errorOf(xhr, 'Could not create the folder.')); });
    });

    validate(this.$renameForm, { rules: { name: nameRules }, messages: { name: nameMessages } }, function () {
      var id = self.state.renameFolderId;
      post(API + '/folders/' + id + '/rename', { name: $.trim(self.$('rename-input').val()) })
        .done(function (res) { self.closeMenus(); self.setFolders(res.folders); self.renderBreadcrumb(); self.render(); notify(res.message); })
        .fail(function (xhr) { notify(errorOf(xhr, 'Could not rename the folder.')); });
    });

    var $edit = this.$('edit-form');
    if ($edit.length) {
      validate($edit, {
        rules: {
          displayName: { required: true, normalizer: trim, maxlength: 255 },
          altText: { maxlength: 255 }
        },
        messages: {
          displayName: { required: 'Display name is required.', maxlength: 'Display name must be at most 255 characters.' },
          altText: { maxlength: 'Alt text must be at most 255 characters.' }
        }
      }, function () {
        var id = self.state.detailFileId;
        var data = { displayName: $.trim(self.$('detail-name').val()), altText: self.$('detail-alt').val() };
        var folderId = self.$('detail-folder').val();
        if (folderId) data.folderId = folderId;
        post(API + '/files/' + id, data)
          .done(function (res) {
            self.replaceFiles([res.file]);
            self.$detail.removeClass('open');
            self.render();
            notify(res.message);
          })
          .fail(function (xhr) { notify(errorOf(xhr, 'Could not save the file.')); });
      });
    }
  };

  P.destroy = function () {
    $(document).off(this.ns);
    if (this.observer) this.observer.disconnect();
    if (this.fromPicker) this.fromPicker.destroy();
    if (this.toPicker) this.toPicker.destroy();
    this.$host.removeData('mediaExplorer').empty();
  };

  P.getSelection = function () {
    return this.selectedFiles();
  };

  window.MediaExplorer = {
    /** Mounts the explorer into `element` (once; mounting again returns the existing instance). */
    mount: function (element, options) {
      var $el = $(element);
      if (!$el.length) return null;
      var existing = $el.data('mediaExplorer');
      if (existing) return existing;
      var instance = new MediaExplorer($el[0], options);
      $el.data('mediaExplorer', instance);
      return instance;
    }
  };
})(window, window.jQuery);
