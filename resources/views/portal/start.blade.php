@extends('portal.layout')

@php
    $central = config('tenancy.central_domains')[0];
    $demoUrl = request()->getScheme().'://demo.'.$central;
    $kontakt = \App\Models\Einstellung::wert('email', config('portal.email'));
@endphp

@push('styles')
<style>
.hero{display:flex;flex-wrap:wrap;gap:48px;align-items:center;padding-top:64px;padding-bottom:80px}
.hero-text{flex:1 1 520px;min-width:0;display:flex;flex-direction:column;gap:26px}
.hero h1{font-size:clamp(40px,5vw,66px)}
.status{flex:1 1 360px;min-width:0;max-width:460px;background:var(--ink);color:#fff;border-radius:28px;padding:28px;display:flex;flex-direction:column;gap:20px}
.schritt{display:flex;gap:14px;align-items:center}
.punkt{width:22px;height:22px;border-radius:50%;flex:0 0 22px}
.band{background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.band .wrap{padding-top:72px;padding-bottom:72px;display:flex;flex-direction:column;gap:36px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:18px}
.karte{background:var(--bg);border-radius:22px;padding:28px;display:flex;flex-direction:column;gap:12px}
.dunkel{max-width:1140px;margin:72px auto 0;background:var(--ink);color:#fff;border-radius:32px;padding:56px 40px;display:flex;flex-wrap:wrap;gap:40px}
.dunkel .grid .karte{background:var(--dark2)}
.branchen{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.branche{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:20px 22px;display:flex;flex-direction:column;gap:4px;text-decoration:none;color:var(--ink)}
.branche:hover{border-color:var(--accent);color:var(--ink)}
</style>
@endpush

@section('content')
<section class="wrap hero">
  <div class="hero-text">
    <span class="pill" style="align-self:flex-start">Pilotphase – die ersten Betriebe sind dabei</span>
    <h1>Der richtige Fachbetrieb. <span style="color:var(--accent)">Live verfolgt</span> bis zur Rechnung.</h1>
    <p class="muted" style="margin:0;font-size:19px;max-width:560px">my-servicecenter verbindet Sie mit Handwerks- und Servicebetrieben aus Ihrer Region. Anfrage per Klick, und danach sehen Sie jederzeit, wann der Monteur kommt.</p>
    <div id="suche" style="display:flex;flex-direction:column;gap:12px;max-width:620px">
      @include('portal._suchformular')
      <div style="display:flex;flex-wrap:wrap;gap:18px;font-size:15px">
        <a href="{{ $demoUrl }}" style="font-weight:600">Beispiel-Betrieb ansehen →</a>
        <a href="#betriebe" style="font-weight:600">Sie sind ein Betrieb? →</a>
      </div>
    </div>
  </div>
  <div class="status" aria-label="Beispiel einer Live-Statusanzeige">
    <span style="font-size:13px;font-weight:600;color:#FDBA8C">Beispiel · Live-Status</span>
    <h2 style="font-size:26px">Ihr Monteur ist unterwegs</h2>
    <div class="schritt"><span class="punkt" style="background:#fff"></span><span>Anfrage gesendet</span></div>
    <div class="schritt"><span class="punkt" style="background:#fff"></span><span>Termin bestätigt</span></div>
    <div class="schritt"><span class="punkt" style="background:var(--accent)"></span><strong>Monteur unterwegs</strong></div>
    <div class="schritt" style="color:var(--light-muted)"><span class="punkt" style="border:2px solid #3B4252"></span><span>Erledigt und bewerten</span></div>
  </div>
</section>

@if($branchen->isNotEmpty())
<section class="wrap" style="padding-bottom:72px;display:flex;flex-direction:column;gap:24px">
  <h2 style="font-size:clamp(28px,3.2vw,40px)">Branchen</h2>
  <div class="branchen">
    @foreach($branchen as $b)
      <a class="branche" href="/{{ $b['slug'] }}">
        <strong style="font-family:'Space Grotesk',sans-serif;font-size:20px">{{ $b['name'] }}</strong>
        <span class="muted" style="font-size:14px">{{ $b['anzahl'] === 0 ? 'demnächst' : ($b['anzahl'] === 1 ? '1 Betrieb' : $b['anzahl'].' Betriebe') }}</span>
      </a>
    @endforeach
  </div>
</section>
@endif

<section id="so-gehts" class="band">
  <div class="wrap">
    <h2 style="font-size:clamp(30px,3.6vw,46px)">In drei Schritten zum Termin</h2>
    <div class="grid">
      <div class="karte"><span style="font-family:'Space Grotesk',sans-serif;font-weight:600;color:var(--accent-ink)">01</span><h3 style="font-size:24px">Betrieb finden</h3><p class="muted" style="margin:0">Jeder Betrieb zeigt Leistungen, Öffnungszeiten und Kontakt auf seiner eigenen Seite.</p></div>
      <div class="karte"><span style="font-family:'Space Grotesk',sans-serif;font-weight:600;color:var(--accent-ink)">02</span><h3 style="font-size:24px">Anfrage senden</h3><p class="muted" style="margin:0">Anliegen wählen, kurz beschreiben, fertig – ohne Konto und ohne Rückruf-Pingpong.</p></div>
      <div class="karte"><span style="font-family:'Space Grotesk',sans-serif;font-weight:600;color:var(--accent-ink)">03</span><h3 style="font-size:24px">Live verfolgen</h3><p class="muted" style="margin:0">Über Ihren persönlichen Link sehen Sie jeden Schritt – wie bei einer Paketverfolgung.</p></div>
    </div>
  </div>
</section>

<section id="betriebe" style="padding:0 20px">
  <div class="dunkel">
    <div style="flex:1 1 380px;min-width:0;display:flex;flex-direction:column;gap:20px">
      <span style="font-size:14px;font-weight:600;color:#FDBA8C">Für Fachbetriebe</span>
      <h2 style="font-size:clamp(32px,4vw,52px)">Mehr Aufträge. Weniger Büro.</h2>
      <p style="margin:0;font-size:18px;color:var(--light-muted)">Wir bringen Ihnen Kunden aus Ihrer Region – und die Software zum Abwickeln ist gleich dabei. Ihre Daten liegen in einer eigenen, getrennten Datenbank auf Servern in Deutschland.</p>
      @if($kontakt)
        <a class="btn" style="align-self:flex-start" href="mailto:{{ $kontakt }}?subject=Pilotbetrieb%20my-servicecenter">Pilotbetrieb werden</a>
        <span style="font-size:14px;color:var(--light-muted)">3 Monate kostenlos testen · {{ $kontakt }}</span>
      @endif
    </div>
    <div class="grid" style="flex:1 1 420px;min-width:0">
      <div class="karte"><h3 style="font-size:20px">Eigene Mini-Website</h3><p style="margin:0;color:var(--light-muted)">Automatisch aus Ihrem Profil, mit Anfrageformular.</p></div>
      <div class="karte"><h3 style="font-size:20px">Anfragen mit allen Infos</h3><p style="margin:0;color:var(--light-muted)">Anliegen, Beschreibung und Kontakt – bevor Sie zurückrufen.</p></div>
      <div class="karte"><h3 style="font-size:20px">Live-Status für Kunden</h3><p style="margin:0;color:var(--light-muted)">Weniger „Wann kommt jemand?“-Anrufe.</p></div>
      <div class="karte"><h3 style="font-size:20px">Team im Griff</h3><p style="margin:0;color:var(--light-muted)">Mitarbeiter, Rollen und bald die Stempeluhr-App.</p></div>
    </div>
  </div>
</section>
@endsection
