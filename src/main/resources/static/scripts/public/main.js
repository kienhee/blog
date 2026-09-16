/* Kienhee — shared behaviour for the public site (jQuery) */
$(function () {
  'use strict';

  /* sticky header + reading progress */
  var $head = $('.site-head');
  var $bar = $('.progress');
  if ($head.length) {
    var onScroll = function () {
      var y = $(window).scrollTop();
      $head.toggleClass('is-scrolled', y > 40);
      if ($bar.length) {
        var max = $(document).height() - $(window).height();
        $bar.css('width', (max > 0 ? Math.min(100, (y / max) * 100) : 0) + '%');
      }
    };
    $(window).on('scroll', onScroll);
    onScroll();
  }

  /* mobile nav */
  $('[data-nav-toggle]').on('click', function () {
    var $btn = $(this);
    var open = $('.site-head').toggleClass('nav-open').hasClass('nav-open');
    $btn.attr('aria-expanded', String(open)).text(open ? '✕' : '☰');
  });

  /* theme */
  /* the saved theme is applied by an inline script in <head>, before first paint */
  $('[data-theme-toggle]').on('click', function () {
    var next = $('html').attr('data-theme') === 'light' ? 'dark' : 'light';
    $('html').attr('data-theme', next);
    try { localStorage.setItem('kienhee-theme', next); } catch (e) {}
  });

  /* search overlay */
  var $ov = $('.overlay');
  $('[data-search-open]').on('click', function () {
    $ov.addClass('open').find('input').trigger('focus');
  });
  $('[data-search-close]').on('click', function () {
    $ov.removeClass('open');
  });
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $ov.hasClass('open')) $ov.removeClass('open');
  });

  /* search as you type */
  var $live = $ov.find('[data-live-search]');
  if ($live.length) {
    var $input = $live.find('input[name="q"]');
    var $latest = $ov.find('[data-search-latest]');
    var $results = $ov.find('[data-search-results]');
    var timer = null;
    var seq = 0;
    var showLatest = function () {
      $results.prop('hidden', true).empty();
      $latest.prop('hidden', false);
    };
    $input.on('input', function () {
      clearTimeout(timer);
      var q = $.trim($input.val());
      if (!q.length) { seq++; showLatest(); return; }
      timer = setTimeout(function () {
        var mine = ++seq;
        $.getJSON($live.attr('data-live-url'), { q: q }).done(function (items) {
          if (mine !== seq) return;
          $latest.prop('hidden', true);
          $results.empty().prop('hidden', false);
          $('<p class="kicker" style="padding:20px 0">')
            .text(items.length ? 'Results' : 'No articles match \u201c' + q + '\u201d').appendTo($results);
          $.each(items, function (_, it) {
            $('<a class="res">').attr('href', it.url)
              .append($('<span class="kicker">').text(it.category))
              .append($('<span style="font-size:18px;font-weight:600">').text(it.title))
              .appendTo($results);
          });
          if (items.length) {
            $('<a class="kicker" style="display:block;padding:16px 0;color:var(--accent)">')
              .attr('href', $live.attr('action') + '?q=' + encodeURIComponent(q))
              .text('See all results \u2192').appendTo($results);
          }
        });
      }, 250);
    });
  }

  /* article: table of contents from the h2 headings */
  var $article = $('[data-article]');
  var $toc = $('[data-toc]');
  if ($article.length && $toc.length) {
    var used = {};
    $article.find('h2').each(function () {
      var $h = $(this);
      var text = $.trim($h.text());
      if (!text) return;
      var id = $h.attr('id');
      if (!id) {
        // Headings are usually Vietnamese: strip the accents first, or every accented letter
        // becomes a separator and the anchor turns into "b-i-vi-t".
        var base = text.normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .replace(/[\u0110\u0111]/g, 'd')
          .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'section';
        id = base;
        for (var n = 2; used[id] || document.getElementById(id); n++) id = base + '-' + n;
        $h.attr('id', id);
      }
      used[id] = true;
      $('<a>').attr('href', '#' + id).text(text).appendTo($toc);
    });
    $toc.prop('hidden', $toc.find('a').length === 0);
  }

  /* newsletter: check the address before the post, so a typo costs no round trip.
     The server validates again (and answers the same way whether or not the address is known),
     this only saves the reader a reload. The honeypot is left alone on purpose: a filled one
     submits as usual and is dropped server-side, so a bot learns nothing. */
  $('[data-newsletter]').each(function () {
    var $form = $(this);
    var $email = $form.find('input[name="email"]');
    var $button = $form.find('button[type="submit"]');
    var $error = $form.find('[data-newsletter-error]');

    function fail(message) {
      $error.text(message).prop('hidden', false);
      $email.attr('aria-invalid', 'true').trigger('focus');
    }

    function clear() {
      $error.prop('hidden', true);
      $email.removeAttr('aria-invalid');
    }

    $email.on('input', clear);

    $form.on('submit', function (e) {
      var value = $.trim($email.val());
      $email.val(value);
      if (!value) {
        e.preventDefault();
        fail('Enter your email address.');
        return;
      }
      // Deliberately loose: the address is confirmed by the email we send, not by this regex.
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
        e.preventDefault();
        fail('That does not look like an email address.');
        return;
      }
      clear();
      // The POST reloads the page, so this only guards against a double click.
      $button.prop('disabled', true).text('Subscribing…');
    });
  });

  /* comments: reply sets the parent id on the single comment form */
  var $cForm = $('[data-comment-form]');
  if ($cForm.length) {
    var $parent = $cForm.find('[data-reply-parent]');
    var $label = $cForm.find('[data-reply-label]');
    var $cancel = $cForm.find('[data-reply-cancel]');
    var $text = $cForm.find('[data-reply-target]');
    var resetReply = function () {
      $parent.val('');
      $label.text('Join the discussion');
      $cancel.prop('hidden', true);
    };

    $(document).on('click', '[data-reply-to]', function () {
      var $btn = $(this);
      $parent.val($btn.attr('data-reply-to'));
      $label.text('Replying to ' + $btn.attr('data-reply-name'));
      $cancel.prop('hidden', false);
      $('html, body').animate({ scrollTop: $cForm.offset().top - 120 }, 200);
      $text.trigger('focus');
    });
    $cancel.on('click', resetReply);
    if (!$parent.val()) resetReply();

    $cForm.on('submit', function () {
      $cForm.find('button[type="submit"]').prop('disabled', true).text('Posting…');
    });
  }

  /* copy buttons */
  $('[data-copy]').on('click', function () {
    var $btn = $(this);
    var sel = $btn.attr('data-copy');
    var text = sel && $(sel).length ? $(sel).text() : window.location.href;
    if (navigator.clipboard) navigator.clipboard.writeText(text).catch(function () {});
    var oldText = $btn.text();
    $btn.text('Copied');
    setTimeout(function () { $btn.text(oldText); }, 1500);
  });

  /* share rail */
  $('[data-share]').on('click', function () {
    var type = $(this).attr('data-share');
    var url = encodeURIComponent(window.location.href);
    var title = encodeURIComponent(document.title);
    var $note = $('.share-note');
    if (type === 'link') {
      if (navigator.clipboard) navigator.clipboard.writeText(window.location.href).catch(function () {});
      if ($note.length) {
        $note.text('Link copied');
        setTimeout(function () { $note.text(''); }, 1600);
      }
      return;
    }
    var target = {
      x: 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title,
      in: 'https://www.linkedin.com/sharing/share-offsite/?url=' + url,
      mail: 'mailto:?subject=' + title + '&body=' + url
    }[type];
    if (!target) return;
    if (type === 'mail') window.location.href = target;
    else window.open(target, '_blank', 'noopener,width=620,height=560');
  });
});
