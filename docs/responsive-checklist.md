# Responsive checklist

One row per page. A page is ticked only after it has been **looked at in a browser** at the three
widths below — not when the CSS merely looks right in a diff.

Widths to check: **390px** (phone), **768px** (tablet), **1280px** (desktop).

What counts as done for a page:

- no horizontal scrollbar on the page body (a table or code block inside its own `overflow-x`
  container is fine);
- nothing clipped or pushed off screen (buttons, breadcrumbs, form controls);
- tap targets still reachable (≥ 40px);
- no grid or width left in a `style="..."` attribute, because a `@media` query cannot override it —
  this was the root cause of every bug found so far.

Breakpoints in use: public `1100 / 900 / 640 / 560`, admin `1180 / 1000 / 760 / 520`.

Legend: `[x]` verified in a real browser · `[~]` fixed in CSS, still needs an eye on it ·
`[ ]` not looked at yet.

## How it is verified

A Playwright sweep drives a real Chromium over every page at the three widths, measures
`scrollWidth - clientWidth` on each, names the elements that stick out (ignoring anything inside a
deliberate `overflow-x` container), collects JS errors and 404s, and writes a screenshot per
page/width. A second script drives the states a page load cannot reach: mobile nav drawer, search
overlay, admin sidebar drawer, offcanvas form, light theme, media explorer.

The scripts live in the session scratchpad, not in the repo (they need the app running and a demo
login). To repeat the run: start the app, then `npm i playwright@1.62.0` and run them — 1.62.0 is the
version matching the Chromium build already in this machine's Playwright cache.

Last sweep: **90 page x width combinations, 0 horizontal overflow, 0 JS errors**, plus six
interactive states at 390px (nav drawer, search overlay, admin sidebar, offcanvas, light theme,
media explorer) — all clean.

## Public site

| | Page | Route | Inline styles left | Notes |
|---|---|---|---|---|
| [x] | `public/index.html` | `/` | 15 | Hero stacks at 900. Newsletter form validated (JS). Cover 1.13:1 frame. |
| [x] | `public/article.html` | `/article/{slug}` | 20 | Hero full-bleed, share rail and TOC restack at 1100/900. TinyMCE content must match `.prose`. |
| [x] | `public/news.html` | `/news` | 3 | Title now `.page-title`. |
| [x] | `public/categories.html` | `/categories` | 5 | Title now `.page-title`. |
| [x] | `public/category.html` | `/category/{slug}` | 6 | Title now `.page-title`; long category names were overflowing at 64px. |
| [x] | `public/author.html` | `/author/{id}` | 11 | Grid moved out of the style attribute; photo height now fluid. |
| [x] | `public/about.html` | `/about` | 8 | Grid + fact rows moved to classes; `!important` hacks removed. |
| [x] | `public/search.html` | `/search`, `/search?tag=` | 6 | `.search-form` wraps; hashtag mode added. |
| [x] | `public/subscribe.html` | `/subscribe` | 15 | Form validated (JS); poster/CTA stack at 900. |
| [x] | `public/newsletter-status.html` | `/subscribe/confirm`, `/unsubscribe` | 15 | `font-size:48px` inline on four headings. |
| [x] | `error/404.html` | error page | 6 | `font-size:80px` inline. |

## Public fragments

| | Fragment | Notes |
|---|---|---|
| [x] | `fragments/public/header.html` | Drawer nav + search overlay. Overlay ESC button was pushed off screen (flex `min-width:auto`) — fixed. |
| [x] | `fragments/public/footer.html` | 4-column grid → 2 at 900 → 1 at 560. Seen on every swept page. |
| [x] | `fragments/public/pagination.html` | Row/thumb layouts; thumb becomes 1:1 at 900. |

## Admin

| | Page | Route | Inline styles left | Notes |
|---|---|---|---|---|
| [x] | `admin/trash/trash.html` | `/admin/trash` | 0 | Rewritten: page `<style>` and inline styles moved to `admin.css`; stacked cards at 760. |
| [x] | `admin/role/roles.html` | `/admin/roles` | 26 | Permission matrix stacks at 760; the "new role" form no longer scrolls away with the role list. |
| [x] | `admin/user/profile.html` | `/admin/profile` | 25 | Password row was 3 columns on a phone — fixed. "Signed-in devices" table is fake data and does not stack. |
| [x] | `admin/post/post-new.html` | `/admin/post/new`, `/edit` | 29 | Cover URL + button now wrap. TinyMCE toolbar wraps to 3–4 rows; editor height fixed at 620. |
| [x] | `admin/analytics/dashboard.html` | `/admin/dashboard` | 50 | Heaviest page. Splits are covered by `!important` rules that should become real classes. |
| [x] | `admin/user/users.html` | `/admin/users` | 24 | DataTables stacking + control row; swept clean at all three widths. |
| [x] | `admin/post/posts.html` | `/admin/posts` | 19 | As above. |
| [x] | `admin/category/categories.html` | `/admin/categories` | 19 | As above; offcanvas form opened and measured at 390. |
| [x] | `admin/hashtag/hashtags.html` | `/admin/hashtags` | 18 | As above. |
| [x] | `admin/comment/comments.html` | `/admin/comments` | 26 | As above; reply box not checked. |
| [x] | `admin/subscriber/subscribers.html` | `/admin/subscribers` | 24 | Send-issue form + past issues split. |
| [x] | `admin/setting/settings.html` | `/admin/settings` | 10 | |
| [x] | `admin/media/media.html` | `/admin/media` | 6 | Explorer changes axis at 1000; full-height layout is the fragile part. |
| [x] | `admin/authentication/login.html` | `/auth/login` | 9 | Own `<head>`, does not use the admin layout. |
| [x] | `admin/authentication/register.html` | `/auth/register` | 8 | |
| [x] | `admin/authentication/forgot.html` | `/auth/forgot` | 9 | |
| [x] | `admin/authentication/reset.html` | `/auth/reset` | 13 | |

## Admin fragments

| | Fragment | Notes |
|---|---|---|
| [x] | `fragments/admin/topbar.html` | Breadcrumb now shrinks and truncates; parent crumb hidden below 520. The "Search everything…" input is decorative — it posts nowhere. |
| [x] | `fragments/admin/sidebar.html` | Off-canvas drawer below 1000; opened and measured at 390. |
| [ ] | `fragments/admin/dialog.html` | Confirm dialog at 390 — not opened by the scripts yet. |

## Out of scope

`templates/mail/*.html` (5 files, 58 inline styles) stay inline: email clients drop `<style>` and
external CSS. They are checked in an email client, not a browser.

## Fixed during the Playwright run

- `admin/roles` overflowed by 33px at 390: the mobile overrides for `.perm-row` sat **earlier** in
  `admin.css` than the base rules I had appended at the end of the file, so at equal specificity the
  base rules won and the matrix never stacked. Overrides now follow their base rules.
- `admin/post/new` overflowed by 13px at 390: the `.help` tooltip was anchored to the 16px "?" icon
  and is laid out even while hidden, so its 320px bubble widened the page. It is now anchored to the
  label line and spans the field width.
- The "Empty trash" button was unreadable — `.btn` is a filled accent button and `.btn.danger` only
  recoloured the text (red on orange). Destructive buttons are outline buttons now.
- TinyMCE's toolbar kept the oxide-dark blue: several skin rules are three classes deep and the skin
  stylesheet is injected after ours, so ties went to the skin. `tinymce-theme.css` selectors now
  carry one extra element (`body .tox.tox-tinymce …`).
- The "new role" form sat inside the horizontally scrolling role list on a phone; it now wraps onto
  its own full-width row.

## Still to do (not responsive bugs)

- [ ] Move the remaining inline styles into CSS, page by page (phase 2 of the plan). The counts in
      the tables above are the work left; a `style="..."` grid is invisible to every breakpoint,
      which is what caused most of the bugs found here.

## Follow-ups noted while auditing

- [ ] `admin/user/profile.html` — "Signed-in devices" is hardcoded demo data (MacBook Pro, iPhone 15,
      Hanoi). Decide: remove it or implement session listing.
- [ ] `fragments/admin/topbar.html` — the topbar search input does nothing. Remove it or wire it up.
- [ ] TinyMCE on a phone: consider a shorter toolbar and a fluid editor height.
