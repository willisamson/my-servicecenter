<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'my-servicecenter – Fachbetriebe finden und live verfolgen')</title>
<meta name="description" content="my-servicecenter verbindet Kunden mit Handwerks- und Servicebetrieben aus ihrer Region – Anfrage per Klick, Live-Status bis zum erledigten Auftrag.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap">
<style>
:root{--ink:#12161F;--muted:#4B5563;--bg:#F5F4F0;--card:#FFFFFF;--line:#E4E1DA;--accent:#C2410C;--accent-soft:#FDEBDD;--accent-ink:#9A3412;--dark2:#1E2430;--light-muted:#C9CDD4}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,system-ui,sans-serif;font-size:16px;line-height:1.55}
a{color:var(--ink)}a:hover{color:var(--accent-ink)}
h1,h2,h3{font-family:'Space Grotesk',sans-serif;letter-spacing:-.03em;line-height:1.05;margin:0}
.wrap{max-width:1180px;margin:0 auto;padding:0 20px}
.top{border-bottom:1px solid var(--line)}
.top .wrap{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px;padding-top:16px;padding-bottom:16px}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:20px;letter-spacing:-.02em}
.logo{width:36px;height:36px;border-radius:10px;background:var(--ink);display:flex;align-items:center;justify-content:center}
.nav{display:flex;flex-wrap:wrap;align-items:center;gap:22px;font-weight:500;font-size:15px}
.nav a{text-decoration:none}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 22px;border-radius:14px;border:0;background:var(--accent);color:#fff;font:inherit;font-weight:600;text-decoration:none}
.btn:hover{color:#fff;background:#9A3412}
.pill{display:inline-block;background:var(--accent-soft);color:var(--accent-ink);font-size:14px;font-weight:600;padding:7px 14px;border-radius:999px}
.muted{color:var(--muted)}
.footer{border-top:1px solid var(--line);margin-top:72px}
.footer .wrap{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;padding-top:28px;padding-bottom:28px;font-size:14px;color:var(--muted)}
.footer a{color:var(--muted)}
</style>
@stack('styles')
</head>
<body>
<header class="top">
  <div class="wrap">
    <a class="brand" href="/">
      @if($logoUrl = \App\Models\Einstellung::logoUrl())
        <img src="{{ $logoUrl }}" alt="my-servicecenter" style="height:40px;width:auto;display:block">
      @else
        <span class="logo" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5"/><path d="M12 12v9"/><path d="M12 12L4 7.5"/></svg></span>
        my-servicecenter
      @endif
    </a>
    <nav class="nav" aria-label="Hauptnavigation">
      <a href="/#so-gehts">So funktioniert’s</a>
      <a href="/#betriebe">Für Betriebe</a>
    </nav>
  </div>
</header>
<main>
@yield('content')
</main>
<footer class="footer">
  <div class="wrap">
    <span>© {{ date('Y') }} my-servicecenter</span>
    <div style="display:flex;gap:18px;flex-wrap:wrap"><a href="/impressum">Impressum</a><a href="/datenschutz">Datenschutz</a></div>
  </div>
</footer>
</body>
</html>
