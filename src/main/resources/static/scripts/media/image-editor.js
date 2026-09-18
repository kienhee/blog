/* Kienhee admin — MediaImageEditor
 *
 * Crop / rotate / straighten / flip / zoom / resize / colour adjustments / format + quality, built on
 * Cropper.js (scripts/lib/cropper.min.js). Everything happens in the browser on a canvas; the result
 * is uploaded to the media JSON API:
 *
 *   MediaImageEditor.canEdit(file)      // JPG, PNG, WEBP, GIF (not SVG, not documents)
 *   MediaImageEditor.open(file, {
 *     canReplace: true,                 // media:edit  -> POST /files/{id}/image       (same id, URL, format)
 *     canCopy: true,                    // media:create -> POST /files/{id}/image-copy (new file, same folder)
 *     onReplaced: function (fileJson) {},
 *     onCopied: function (fileJson) {}
 *   });
 *
 * One modal is created on first use and reused. It relies on the admin layout for jQuery, the CSRF
 * meta tag and khToast.
 */
(function (window, $) {
  'use strict';

  var API = '/admin/api/media';
  var EDITABLE = /^image\/(jpeg|png|webp|gif)$/i;
  var EXT = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp', 'image/gif': 'gif' };
  var LABEL = { 'image/jpeg': 'JPG', 'image/png': 'PNG', 'image/webp': 'WEBP', 'image/gif': 'GIF' };
  var MAX_SIDE = 10000;
  var RATIOS = [
    [khT('js.ie.free'), 'free'], [khT('js.ie.original'), 'original'], ['1:1', 1], ['4:3', 4 / 3], ['3:2', 3 / 2], ['16:9', 16 / 9], ['9:16', 9 / 16]
  ];
  // [key, label, min, max, default]
  var ADJUST = [
    ['brightness', khT('js.ie.brightness'), 0, 200, 100],
    ['contrast', khT('js.ie.contrast'), 0, 200, 100],
    ['saturate', khT('js.ie.saturation'), 0, 200, 100],
    ['grayscale', khT('js.ie.grayscale'), 0, 100, 0],
    ['sepia', 'Sepia', 0, 100, 0]
  ];

  var I = function (paths) {
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + paths + '</svg>';
  };
  var TOOLS = [
    ['rotate-left', khT('js.ie.rotate_left'), I('<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>')],
    ['rotate-right', khT('js.ie.rotate_right'), I('<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>')],
    ['flip-h', khT('js.ie.flip_h'), I('<path d="M12 3v18"/><path d="M8 7 3 12l5 5V7Z"/><path d="m16 7 5 5-5 5V7Z"/>')],
    ['flip-v', khT('js.ie.flip_v'), I('<path d="M3 12h18"/><path d="M7 8 12 3l5 5H7Z"/><path d="m7 16 5 5 5-5H7Z"/>')],
    ['zoom-in', khT('js.ie.zoom_in'), I('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M11 8v6"/><path d="M8 11h6"/>')],
    ['zoom-out', khT('js.ie.zoom_out'), I('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M8 11h6"/>')]
  ];

  function csrfToken() {
    return $('meta[name="_csrf"]').attr('content') || $('input[name="_csrf"]').first().val() || '';
  }

  function toast(message) {
    if (window.khToast && message) window.khToast(message);
  }

  function withVersion(url, version) {
    if (!url) return url;
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'v=' + encodeURIComponent(version || Date.now());
  }

  function clampInt(value, min, max) {
    var n = parseInt(value, 10);
    if (isNaN(n)) return null;
    return Math.max(min, Math.min(max, n));
  }

  function baseName(name) {
    var n = String(name || 'image');
    var dot = n.lastIndexOf('.');
    return dot > 0 ? n.slice(0, dot) : n;
  }

  function markup() {
    var ratios = RATIOS.map(function (r, i) {
      return '<button type="button" class="btn btn-ghost btn-xs ie-chip' + (i === 0 ? ' active' : '') + '" data-ie-ratio="' + i + '">' + r[0] + '</button>';
    }).join('');
    var tools = TOOLS.map(function (t) {
      return '<button type="button" class="btn btn-ghost btn-xs ie-tool" data-ie-act="' + t[0] + '" title="' + t[1] + '" aria-label="' + t[1] + '">' + t[2] + '</button>';
    }).join('');
    var sliders = ADJUST.map(function (a) {
      return '<label class="ie-range"><span>' + a[1] + '</span>' +
        '<input type="range" min="' + a[2] + '" max="' + a[3] + '" value="' + a[4] + '" data-ie-adjust="' + a[0] + '">' +
        '<output>' + a[4] + '%</output></label>';
    }).join('');

    return '' +
      '<div class="ie-modal" role="dialog" aria-modal="true" aria-label="' + khT('js.ie.title') + '">' +
      '  <div class="ie-box">' +
      '    <div class="ie-head">' +
      '      <div class="ie-head-text"><div class="kicker">' + khT('js.ie.title') + '</div><div class="ie-title" data-ie="title"></div></div>' +
      '      <button type="button" class="icon-btn" data-ie="close" aria-label="' + khT('js.close') + '">' + I('<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>') + '</button>' +
      '    </div>' +
      '    <div class="ie-main">' +
      '      <div class="ie-stage"><img data-ie="img" alt=""><div class="ie-loading dim" data-ie="loading">' + khT('js.ie.loading') + '</div></div>' +
      '      <div class="ie-side">' +
      '        <section><div class="kicker">' + khT('js.ie.crop') + '</div><div class="ie-chips">' + ratios + '</div>' +
      '          <div class="dim ie-hint" data-ie="crop-info"></div></section>' +
      '        <section><div class="kicker">' + khT('js.ie.rotate_flip') + '</div><div class="ie-tools">' + tools + '</div>' +
      '          <label class="ie-range"><span>' + khT('js.ie.straighten') + '</span><input type="range" min="-45" max="45" step="1" value="0" data-ie="straighten"><output data-ie="straighten-val">0°</output></label></section>' +
      '        <section><div class="kicker">' + khT('js.ie.resize') + '</div>' +
      '          <div class="ie-size">' +
      '            <label><span>' + khT('js.ie.width') + '</span><input class="input" type="number" min="1" max="' + MAX_SIDE + '" data-ie="w"></label>' +
      '            <button type="button" class="btn btn-ghost btn-xs ie-lock on" data-ie="lock" aria-pressed="true" title="' + khT('js.ie.keep_ratio') + '">' + I('<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>') + '</button>' +
      '            <label><span>' + khT('js.ie.height') + '</span><input class="input" type="number" min="1" max="' + MAX_SIDE + '" data-ie="h"></label>' +
      '          </div>' +
      '          <p class="dim ie-hint">' + khT('js.ie.size_hint') + '</p></section>' +
      '        <section><div class="kicker">' + khT('js.ie.adjust') + '</div>' + sliders + '</section>' +
      '        <section><div class="kicker">' + khT('js.ie.export') + '</div>' +
      '          <label class="field"><span>' + khT('js.ie.format') + '</span><select class="select" data-ie="format"></select></label>' +
      '          <label class="ie-range" data-ie="quality-row"><span>' + khT('js.ie.quality') + '</span><input type="range" min="40" max="100" value="90" data-ie="quality"><output data-ie="quality-val">90</output></label>' +
      '          <p class="dim ie-hint" data-ie="format-hint"></p></section>' +
      '      </div>' +
      '    </div>' +
      '    <div class="ie-foot">' +
      '      <button type="button" class="btn btn-ghost" data-ie="reset">' + khT('js.ie.reset') + '</button>' +
      '      <span class="ie-spacer"></span>' +
      '      <span class="ie-status dim" data-ie="status" role="status"></span>' +
      '      <button type="button" class="btn btn-ghost" data-ie="cancel">' + khT('js.cancel') + '</button>' +
      '      <button type="button" class="btn btn-ghost" data-ie="copy">' + khT('js.ie.save_copy') + '</button>' +
      '      <button type="button" class="btn" data-ie="replace">' + khT('js.ie.replace') + '</button>' +
      '    </div>' +
      '  </div>' +
      '</div>';
  }

  function Editor() {
    var self = this;
    this.$root = $(markup()).appendTo(document.body);
    this.$ = function (name) { return self.$root.find('[data-ie="' + name + '"]'); };
    this.$img = this.$('img');
    this.cropper = null;
    this.file = null;
    this.opts = {};
    this.busy = false;
    this.bind();
  }

  var P = Editor.prototype;

  /* ---------------- state ---------------- */

  P.resetState = function () {
    this.state = {
      rotation: 0,        // multiples of 90
      straighten: 0,      // -45..45
      scaleX: 1,
      scaleY: 1,
      sizeTouched: false,
      ratio: 0,
      adjust: {}
    };
    var st = this.state;
    ADJUST.forEach(function (a) { st.adjust[a[0]] = a[4]; });
  };

  P.filterString = function () {
    var a = this.state.adjust;
    return 'brightness(' + a.brightness + '%) contrast(' + a.contrast + '%) saturate(' + a.saturate + '%) ' +
      'grayscale(' + a.grayscale + '%) sepia(' + a.sepia + '%)';
  };

  P.hasAdjustments = function () {
    var a = this.state.adjust;
    return ADJUST.some(function (x) { return a[x[0]] !== x[4]; });
  };

  /** The MIME type the canvas will be encoded as. GIF can't be encoded by browsers, so it becomes PNG. */
  P.outputType = function () {
    var chosen = this.$('format').val();
    if (chosen === 'original') return this.file.contentType === 'image/gif' ? 'image/png' : this.file.contentType;
    return chosen;
  };

  /* ---------------- open / close ---------------- */

  P.open = function (file, opts) {
    var self = this;
    this.file = file;
    this.opts = opts || {};
    this.resetState();
    this.setBusy(false);
    this.status('');

    this.$('title').text(file.name || 'Image');
    var $format = this.$('format').empty();
    var original = LABEL[file.contentType] || khT('js.ie.original');
    $('<option value="original">').text(khT('js.ie.original_format', original)).appendTo($format);
    ['image/jpeg', 'image/png', 'image/webp'].forEach(function (type) {
      if (type !== file.contentType) $('<option>').val(type).text(LABEL[type]).appendTo($format);
    });
    this.$('quality').val(90);
    this.$('quality-val').text('90');
    this.$root.find('[data-ie-ratio]').removeClass('active').filter('[data-ie-ratio="0"]').addClass('active');
    this.$('straighten').val(0);
    this.$('straighten-val').text('0°');
    this.$root.find('[data-ie-adjust]').each(function () {
      var key = $(this).attr('data-ie-adjust');
      $(this).val(self.state.adjust[key]).next('output').text(self.state.adjust[key] + '%');
    });
    this.$('lock').addClass('on').attr('aria-pressed', 'true');
    this.$('replace').toggle(!!this.opts.canReplace);
    this.$('copy').toggle(!!this.opts.canCopy);
    this.syncFormat();

    this.destroyCropper();
    this.$('loading').text(khT('js.ie.loading')).show();
    this.$img.off('load.ie error.ie')
      .one('load.ie', function () { self.initCropper(); })
      .one('error.ie', function () { self.$('loading').text(khT('js.ie.load_failed')); })
      .attr('src', withVersion(file.url, file.version));

    this.$root.addClass('open');
    $(document).on('keydown.mediaImageEditor', function (e) {
      if (e.key === 'Escape' && !self.busy) self.close();
    });
  };

  P.close = function () {
    var self = this;
    this.$root.removeClass('open');
    $(document).off('keydown.mediaImageEditor');
    setTimeout(function () {
      if (!self.$root.hasClass('open')) {
        self.destroyCropper();
        self.$img.removeAttr('src');
      }
    }, 250);
  };

  P.initCropper = function () {
    var self = this;
    this.$('loading').hide();
    this.cropper = new window.Cropper(this.$img[0], {
      viewMode: 1,
      autoCropArea: 1,
      background: false,
      checkOrientation: false,
      responsive: true,
      toggleDragModeOnDblclick: true,
      ready: function () { self.applyPreviewFilter(); },
      crop: function (e) { self.onCrop(e.detail); }
    });
  };

  P.destroyCropper = function () {
    if (this.cropper) {
      this.cropper.destroy();
      this.cropper = null;
    }
  };

  /* ---------------- editing ---------------- */

  P.onCrop = function (d) {
    var w = Math.max(1, Math.round(d.width));
    var h = Math.max(1, Math.round(d.height));
    this.$('crop-info').text(khT('js.ie.crop_info', w, h));
    if (!this.state.sizeTouched) {
      this.$('w').val(w);
      this.$('h').val(h);
    }
  };

  P.cropSize = function () {
    var d = this.cropper ? this.cropper.getData(true) : null;
    return d && d.width && d.height ? d : { width: this.file.width || 1, height: this.file.height || 1 };
  };

  P.applyRotation = function () {
    if (this.cropper) this.cropper.rotateTo(this.state.rotation + this.state.straighten);
  };

  P.applyPreviewFilter = function () {
    var filter = this.hasAdjustments() ? this.filterString() : '';
    this.$root.find('.cropper-canvas img, .cropper-view-box img').css('filter', filter);
  };

  P.setRatio = function (index) {
    var ratio = RATIOS[index][1];
    var value = NaN;
    if (ratio === 'original') {
      var data = this.cropper ? this.cropper.getImageData() : null;
      value = data && data.naturalHeight ? data.naturalWidth / data.naturalHeight : NaN;
    } else if (ratio !== 'free') {
      value = ratio;
    }
    this.state.ratio = index;
    this.state.sizeTouched = false;
    this.$root.find('[data-ie-ratio]').removeClass('active').filter('[data-ie-ratio="' + index + '"]').addClass('active');
    if (this.cropper) this.cropper.setAspectRatio(value);
  };

  P.action = function (act) {
    if (!this.cropper) return;
    var st = this.state;
    switch (act) {
      case 'rotate-left': st.rotation -= 90; st.sizeTouched = false; this.applyRotation(); break;
      case 'rotate-right': st.rotation += 90; st.sizeTouched = false; this.applyRotation(); break;
      case 'flip-h': st.scaleX = -st.scaleX; this.cropper.scaleX(st.scaleX); break;
      case 'flip-v': st.scaleY = -st.scaleY; this.cropper.scaleY(st.scaleY); break;
      case 'zoom-in': this.cropper.zoom(0.1); break;
      case 'zoom-out': this.cropper.zoom(-0.1); break;
    }
  };

  P.reset = function () {
    if (this.busy) return;
    var file = this.file;
    var opts = this.opts;
    if (this.cropper) this.cropper.reset();
    this.open(file, opts);
  };

  P.syncFormat = function () {
    var type = this.outputType();
    var lossy = type === 'image/jpeg' || type === 'image/webp';
    this.$('quality-row').toggle(lossy);

    var hints = [];
    var sameFormat = type === this.file.contentType;
    if (this.file.contentType === 'image/gif') {
      hints.push(khT('js.ie.gif_hint'));
    } else if (!sameFormat) {
      hints.push(khT('js.ie.format_hint', LABEL[type]));
    }
    if (this.opts.canReplace && this.file.used && sameFormat) {
      hints.push(khT('js.ie.in_use_hint'));
    }
    this.$('format-hint').text(hints.join(' '));
    this.$('replace').prop('disabled', !sameFormat).attr('title', sameFormat ? '' : khT('js.ie.replace_disabled'));
  };

  /* ---------------- export & save ---------------- */

  /** Applies brightness/contrast/saturation/grayscale/sepia by hand when canvas filters aren't supported. */
  function manualFilter(ctx, w, h, a) {
    var img = ctx.getImageData(0, 0, w, h);
    var d = img.data;
    var b = a.brightness / 100, c = a.contrast / 100, s = a.saturate / 100, g = a.grayscale / 100, sp = a.sepia / 100;
    for (var i = 0; i < d.length; i += 4) {
      var r = d[i] * b, gr = d[i + 1] * b, bl = d[i + 2] * b;
      r = (r - 128) * c + 128; gr = (gr - 128) * c + 128; bl = (bl - 128) * c + 128;
      var lum = 0.2126 * r + 0.7152 * gr + 0.0722 * bl;
      r = lum + (r - lum) * s; gr = lum + (gr - lum) * s; bl = lum + (bl - lum) * s;
      lum = 0.2126 * r + 0.7152 * gr + 0.0722 * bl;
      r += (lum - r) * g; gr += (lum - gr) * g; bl += (lum - bl) * g;
      var sr = 0.393 * r + 0.769 * gr + 0.189 * bl, sg = 0.349 * r + 0.686 * gr + 0.168 * bl, sb = 0.272 * r + 0.534 * gr + 0.131 * bl;
      r += (sr - r) * sp; gr += (sg - gr) * sp; bl += (sb - bl) * sp;
      d[i] = r < 0 ? 0 : r > 255 ? 255 : r;
      d[i + 1] = gr < 0 ? 0 : gr > 255 ? 255 : gr;
      d[i + 2] = bl < 0 ? 0 : bl > 255 ? 255 : bl;
    }
    ctx.putImageData(img, 0, 0);
  }

  P.exportBlob = function () {
    var self = this;
    var deferred = $.Deferred();
    var type = this.outputType();
    var crop = this.cropSize();
    var w = clampInt(this.$('w').val(), 1, MAX_SIDE) || Math.round(crop.width);
    var h = clampInt(this.$('h').val(), 1, MAX_SIDE) || Math.round(crop.height);

    var canvas = this.cropper.getCroppedCanvas({
      width: w,
      height: h,
      imageSmoothingEnabled: true,
      imageSmoothingQuality: 'high',
      // JPEG has no transparency: straightened corners become white instead of black.
      fillColor: type === 'image/jpeg' ? '#ffffff' : 'transparent'
    });
    if (!canvas) return deferred.reject(khT('js.ie.render_failed')).promise();

    if (this.hasAdjustments()) {
      var out = document.createElement('canvas');
      out.width = canvas.width;
      out.height = canvas.height;
      var ctx = out.getContext('2d');
      if (type === 'image/jpeg') {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, out.width, out.height);
      }
      if (typeof ctx.filter === 'string') {
        ctx.filter = this.filterString();
        ctx.drawImage(canvas, 0, 0);
        ctx.filter = 'none';
      } else {
        ctx.drawImage(canvas, 0, 0);
        manualFilter(ctx, out.width, out.height, this.state.adjust);
      }
      canvas = out;
    }

    var quality = (parseInt(this.$('quality').val(), 10) || 90) / 100;
    canvas.toBlob(function (blob) {
      if (!blob) return deferred.reject(khT('js.ie.encode_failed'));
      if (blob.type !== type) {
        return deferred.reject(khT('js.ie.format_unsupported', LABEL[type]));
      }
      deferred.resolve(blob, type, w, h);
    }, type, quality);
    return deferred.promise();
  };

  P.save = function (mode) {
    var self = this;
    if (this.busy || !this.cropper) return;
    var replacing = mode === 'replace';
    if (replacing && this.outputType() !== this.file.contentType) return;
    if (replacing && this.file.used && !window.confirm(khT('js.ie.confirm_replace'))) return;

    this.setBusy(true);
    this.status(khT('js.ie.rendering'));
    this.exportBlob().done(function (blob, type) {
      var name = replacing ? self.file.name : baseName(self.file.name) + '-edited.' + EXT[type];
      var fd = new FormData();
      fd.append('_csrf', csrfToken());
      fd.append('file', blob, name);
      self.status(khT('js.saving'));
      $.ajax({
        url: API + '/files/' + self.file.id + (replacing ? '/image' : '/image-copy'),
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': csrfToken() },
        dataType: 'json'
      }).done(function (res) {
        toast(res.message);
        self.setBusy(false);
        self.close();
        var cb = replacing ? self.opts.onReplaced : self.opts.onCopied;
        if (typeof cb === 'function') cb(res.file);
      }).fail(function (xhr) {
        var message = (xhr.responseJSON && xhr.responseJSON.message) || khT('js.ie.save_failed');
        self.setBusy(false);
        self.status(message, true);
        toast(message);
      });
    }).fail(function (message) {
      self.setBusy(false);
      self.status(message, true);
      toast(message);
    });
  };

  P.setBusy = function (busy) {
    this.busy = busy;
    this.$root.find('.ie-foot button, .ie-side button, .ie-side input, .ie-side select').prop('disabled', busy);
    if (!busy && this.file) this.syncFormat();
  };

  P.status = function (text, isError) {
    this.$('status').text(text || '').toggleClass('err', !!isError);
  };

  /* ---------------- events ---------------- */

  P.bind = function () {
    var self = this;
    var $root = this.$root;

    this.$('close').on('click', function () { if (!self.busy) self.close(); });
    this.$('cancel').on('click', function () { if (!self.busy) self.close(); });
    this.$('reset').on('click', function () { self.reset(); });
    this.$('replace').on('click', function () { self.save('replace'); });
    this.$('copy').on('click', function () { self.save('copy'); });

    $root.on('click', '[data-ie-ratio]', function () { self.setRatio(parseInt($(this).attr('data-ie-ratio'), 10)); });
    $root.on('click', '[data-ie-act]', function () { self.action($(this).attr('data-ie-act')); });

    this.$('straighten').on('input', function () {
      self.state.straighten = parseInt(this.value, 10) || 0;
      self.$('straighten-val').text(self.state.straighten + '°');
      self.state.sizeTouched = false;
      self.applyRotation();
    });

    $root.on('input', '[data-ie-adjust]', function () {
      var key = $(this).attr('data-ie-adjust');
      self.state.adjust[key] = parseInt(this.value, 10);
      $(this).next('output').text(this.value + '%');
      self.applyPreviewFilter();
    });

    this.$('lock').on('click', function () {
      var on = !$(this).hasClass('on');
      $(this).toggleClass('on', on).attr('aria-pressed', String(on));
    });

    var resize = function (changed) {
      self.state.sizeTouched = true;
      if (!self.$('lock').hasClass('on')) return;
      var crop = self.cropSize();
      var ratio = crop.width / crop.height;
      if (changed === 'w') {
        var w = clampInt(self.$('w').val(), 1, MAX_SIDE);
        if (w) self.$('h').val(Math.max(1, Math.round(w / ratio)));
      } else {
        var h = clampInt(self.$('h').val(), 1, MAX_SIDE);
        if (h) self.$('w').val(Math.max(1, Math.round(h * ratio)));
      }
    };
    this.$('w').on('input', function () { resize('w'); });
    this.$('h').on('input', function () { resize('h'); });

    this.$('format').on('change', function () { self.syncFormat(); });
    this.$('quality').on('input', function () { self.$('quality-val').text(this.value); });
  };

  /* ---------------- public API ---------------- */

  var instance = null;

  window.MediaImageEditor = {
    canEdit: function (file) {
      return !!(file && file.kind === 'image' && EDITABLE.test(file.contentType || ''));
    },
    open: function (file, opts) {
      if (!window.Cropper) {
        toast(khT('js.ie.not_loaded'));
        return;
      }
      if (!this.canEdit(file)) {
        toast(khT('js.ie.not_editable'));
        return;
      }
      if (!instance) instance = new Editor();
      instance.open(file, opts);
    },
    /** Appends the file's version so an edited image isn't served from cache. */
    withVersion: withVersion
  };
})(window, jQuery);
