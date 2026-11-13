@extends('portal.layout')

@section('title', $titel.' – my-servicecenter')
@section('description', $titel.': Fachbetriebe aus der Region auf my-servicecenter. Anfrage per Klick senden und den Auftrag live verfolgen.')

@php
    $kontakt = \App\Models\Einstellung::wert('email', config('portal.email'));
@endphp

@push('head')
  @if(! $indexieren)
    <meta name="robots" content="noindex, follow">
  @endif
  @if($kanonisch)
    <link rel="canonical" href="{{ $kanonisch }}">
  @endif
@endpush

@push('styles')
<style>
.kopf{padding-top:48px;padding-bottom:32px;display:flex;flex-direction:column;gap:18px}
.kopf h1{font-size:clamp(34px,4.4vw,54px)}
.pfad{font-size:14px;color:var(--muted)}
.pfad a{color:var(--muted)}
.treffer{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px}
.betrieb{background:var(--card);border:1px solid var(--line);border-radius:22px;padding:26px;display:flex;flex-direction:column;gap:12px;text-decoration:none;color:var(--ink);transition:border-color .15s,transform .15s}
.betrieb:hover{border-color:var(--accent);transform:translateY(-2px);color:var(--ink)}
.betrieb .zeile{display:flex;gap:14px;align-items:center}
.initialen{width:48px;height:48px;flex:0 0 48px;border-radius:14px;background:var(--ink);color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',sans-serif;font-weight:700}
.tags{display:flex;flex-wrap:wrap;gap:6px}
.tag{font-size:13px;background:var(--bg);border-radius:999px;padding:4px 10px;color:var(--muted)}
.notdienst{font-size:13px;font-weight:600;background:var(--accent-soft);color:var(--accent-ink);border-radius:999px;padding:4px 10px}
.orte{display:flex;flex-wrap:wrap;gap:10px}
.orte a{background:var(--card);border:1px solid var(--line);border-radius:999px;padding:8px 16px;text-decoration:none;font-weight:500}
.orte a:hover{border-color:var(--accent)}
.leer{background:var(--card);border:1px solid var(--line);border-radius:22px;padding:36px;display:flex;flex-direction:column;gap:14px;max-width:720px}
</style>
@endpush

@section('content')
<section class="wrap kopf">
  <nav class="pfad" aria-label="Brotkrumen">
    <a href="/">Start</a>
    @if($branche)
      › <a href="/{{ $branche->slug }}">{{ $branche->name }}</a>
    @endif
    @if($suchOrt !== '')
      › {{ $suchOrt }}
    @endif
  </nav>
  <h1>{{ $titel }}</h1>
  @if($untertitel)
    <p class="muted" style="margin:0;font-size:18px">{{ $untertitel }} · Anfrage direkt beim Betrieb, Status live verfolgen</p>
  @endif
  <div style="max-width:760px">
    @include('portal._suchformular', ['gewaehlt' => $branche?->slug, 'ortWert' => $suchOrt])
  </div>
</section>

<section class="wrap" style="display:flex;flex-direction:column;gap:32px">
  @if($betriebe->isEmpty())
    <div class="leer">
      <h2 style="font-size:26px">Hier ist noch kein Betrieb eingetragen</h2>
      <p class="muted" style="margin:0">Wir starten gerade Region für Region. Versuchen Sie einen Nachbarort oder eine andere Branche.</p>
      @if($kontakt)
        <p style="margin:0">Sie sind selbst ein Betrieb in dieser Gegend? <a href="mailto:{{ $kontakt }}?subject=Pilotbetrieb%20my-servicecenter" style="font-weight:600">Jetzt Pilotbetrieb werden →</a></p>
      @endif
    </div>
  @else
    <div class="treffer">
      @foreach($betriebe as $b)
        <a class="betrieb" href="{{ $b->subdomainUrl() }}">
          <div class="zeile">
            @if($logo = $b->logoUrl())
              <img src="{{ $logo }}" alt="" loading="lazy" style="width:48px;height:48px;flex:0 0 48px;border-radius:14px;object-fit:contain;background:#fff;border:1px solid var(--line)">
            @else
              <span class="initialen" aria-hidden="true">{{ $b->initialen() }}</span>
            @endif
            <div style="min-width:0">
              <h2 style="font-size:21px;overflow-wrap:anywhere">{{ $b->name }}</h2>
              <span class="muted" style="font-size:14px">{{ $b->brancheLabel() }}@if($b->ort) · {{ trim(($b->plz ?? '').' '.$b->ort) }}@endif</span>
            </div>
          </div>
          @if($b->kurzbeschreibung() !== '')
            <p class="muted" style="margin:0;font-size:15px">{{ $b->kurzbeschreibung() }}</p>
          @endif
          @if($b->notdienst || ! empty($b->leistungen))
            <div class="tags">
              @if($b->notdienst)<span class="notdienst">Notdienst</span>@endif
              @foreach(array_slice((array) $b->leistungen, 0, 4) as $leistung)
                <span class="tag">{{ $leistung }}</span>
              @endforeach
            </div>
          @endif
          <span style="margin-top:auto;font-weight:600;color:var(--accent-ink)">Anfrage senden →</span>
        </a>
      @endforeach
    </div>
    @if($mehr)
      <p class="muted" style="margin:0">Es gibt noch mehr Treffer. Bitte grenzen Sie die Suche mit Ort oder Branche ein.</p>
    @endif
  @endif

  @if($branche && $orte->isNotEmpty())
    <div style="display:flex;flex-direction:column;gap:14px">
      <h2 style="font-size:24px">{{ $branche->name }} nach Ort</h2>
      <div class="orte">
        @foreach($orte as $ortSlug => $o)
          <a href="/{{ $branche->slug }}/{{ $ortSlug }}">{{ $o['name'] }} <span class="muted">({{ $o['anzahl'] }})</span></a>
        @endforeach
      </div>
    </div>
  @endif
</section>
@endsection
