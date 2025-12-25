<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>@yield('title')</title>
</head>

<body style="margin:0; padding:0; background:#F7F8FA; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif; color:#1F2937;">

<div style="padding:28px 16px; background:#F7F8FA;">
  <div style="max-width:620px; margin:0 auto; background:#FFFFFF; border-radius:8px; box-shadow:0 6px 18px rgba(18,24,32,0.06); border:1px solid #E6E9EE; overflow:hidden;">

    <!-- Header -->
    <div style="padding:20px 24px; background:linear-gradient(90deg,#FF6A3D,#E0552A); color:#fff; display:flex; align-items:center; gap:14px;">
      <div style="font-size:18px; font-weight:700;">{{ config('app.name') }}</div>
    </div>

    <!-- Body -->
    <div style="padding:28px 24px;">
      @yield('content')
    </div>

    <!-- Footer -->
    <div style="padding:16px 24px; text-align:center; font-size:13px; color:#6B7280;">
      © {{ date('Y') }} {{ config('app.name') }} — All rights reserved.
    </div>

  </div>
</div>

</body>
</html>
