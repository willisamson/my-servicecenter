<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', $betrieb->name)</title>
@stack('meta')
@if(tenant('id') === 'demo')<meta name="robots" content="noindex">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap">
<style>
:root{--ink:#12161F;--muted:#4B5563;--bg:#F5F4F0;--card:#FFFFFF;--line:#E4E1DA;--accent:#C2410C;--accent-soft:#FDEBDD;--accent-ink:#9A3412;--dark2:#1E2430}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,system-ui,sans-serif;font-size:16px;line-height:1.55}
a{color:var(--ink)}a:hover{color:var(--accent-ink)}
h1,h2,h3{font-family:'Space Grotesk',sans-serif;letter-spacing:-.03em;line-height:1.05;margin:0}
.wrap{max-width:1180px;margin:0 auto;padding:0 20px}
.top{border-bottom:1px solid var(--line)}
.top .wrap{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;padding-top:16px;padding-bottom:16px}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:19px}
.logo{width:44px;height:44px;border-radius:12px;background:var(--accent-soft);color:var(--accent-ink);display:flex;align-items:center;justify-content:center;font-weight:700}
.nav{display:flex;flex-wrap:wrap;align-items:center;gap:20px;font-weight:500;font-size:15px}
.nav a{text-decoration:none}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 22px;border-radius:14px;border:0;background:var(--accent);color:#fff;font:inherit;font-weight:600;text-decoration:none;cursor:pointer}
.btn:hover{color:#fff;background:#9A3412}
.btn-dark{background:var(--ink)}
.pill{display:inline-block;background:var(--card);border:1px solid var(--line);font-size:13px;font-weight:600;padding:6px 12px;border-radius:999px}
.pill-accent{background:var(--accent-soft);border-color:var(--accent-soft);color:var(--accent-ink)}
.card{background:var(--card);border:1px solid var(--line);border-radius:24px;padding:24px}
.muted{color:var(--muted)}
label{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)}
input,select,textarea{font:inherit;font-size:15px;color:var(--ink);background:#fff;border:1px solid var(--line);border-radius:12px;padding:11px 12px;width:100%}
input:focus,select:focus,textarea:focus{outline:2px solid var(--accent);outline-offset:1px;border-color:var(--accent)}
.fehler{color:#B91C1C;font-size:13px;font-weight:500}
.footer{border-top:1px solid var(--line);margin-top:72px}
.footer .wrap{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;padding-top:28px;padding-bottom:28px;font-size:14px;color:var(--muted)}
.footer a{color:var(--muted)}
</style>
@stack('styles')
</head>
<body>
<header class="top">
  <div class="wrap">
    <a class="brand" href="{{ route('betrieb.website') }}">
      @if($logoUrl = $betrieb->logoUrl())
        <img src="{{ $logoUrl }}" alt="{{ $betrieb->name }}" style="height:48px;width:auto;max-width:220px;object-fit:contain;display:block">
      @else
        <span class="logo" aria-hidden="true">{{ $betrieb->initialen() }}</span>{{ $betrieb->name }}
      @endif
    </a>
    @yield('nav')
  </div>
</header>
<main>
@yield('content')
</main>
<footer class="footer">
  <div class="wrap">
    <div style="display:flex;gap:18px;flex-wrap:wrap"><a href="{{ route('betrieb.impressum') }}">Impressum</a><a href="{{ route('betrieb.datenschutz') }}">Datenschutz</a></div>
    <span>Erstellt mit my-servicecenter</span>
  </div>
</footer>
</body>
</html>
