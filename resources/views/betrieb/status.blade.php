@extends('betrieb.layout')

@section('title', 'Ihre Anfrage – '.$betrieb->name)

@push('meta')
<meta name="robots" content="noindex">
@if(!in_array($anfrage->status, ['erledigt', 'abgelehnt']))
<meta http-equiv="refresh" content="60">
@endif
@endpush

@php
    $schritte = [
        'neu' => 'Anfrage gesendet',
        'angenommen' => 'Anfrage angenommen',
        'termin' => 'Termin bestätigt',
        'unterwegs' => 'Monteur unterwegs',
        'erledigt' => 'Erledigt',
    ];
    $reihenfolge = array_keys($schritte);
    $aktuell = array_search($anfrage->status, $reihenfolge, true);
    $termin = $anfrage->termin_am?->timezone('Europe/Berlin');
    [$titel, $text] = match ($anfrage->status) {
        'angenommen' => ['Ihre Anfrage wurde angenommen', 'Sie erhalten in Kürze einen Terminvorschlag.'],
        'termin' => ['Ihr Termin steht', $termin ? 'am '.$termin->format('d.m.Y').' um '.$termin->format('H:i').' Uhr' : 'Der Betrieb meldet sich mit den Details.'],
        'unterwegs' => ['Ihr Monteur ist unterwegs', 'Er ist in Kürze bei Ihnen.'],
        'erledigt' => ['Auftrag erledigt', 'Vielen Dank für Ihr Vertrauen.'],
        'abgelehnt' => ['Anfrage leider nicht möglich', 'Bitte wenden Sie sich direkt an den Betrieb.'],
        default => ['Ihre Anfrage ist eingegangen', 'Der Betrieb meldet sich in Kürze bei Ihnen.'],
    };
@endphp

@section('content')
<section class="wrap" style="max-width:640px;padding-top:40px;display:flex;flex-direction:column;gap:16px">
  @if(session('neu'))
    <div class="card" style="background:var(--accent-soft);border-color:var(--accent-soft);color:var(--accent-ink);padding:18px 20px">
      <strong>Danke! Ihre Anfrage ist angekommen.</strong><br>
      Speichern Sie diese Seite als Lesezeichen – hier sehen Sie jederzeit den aktuellen Stand.
    </div>
  @endif

  <div class="card" style="background:var(--ink);border-color:var(--ink);color:#fff;display:flex;flex-direction:column;gap:10px">
    @if($anfrage->status !== 'erledigt' && $anfrage->status !== 'abgelehnt')
      <span style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#FDBA8C"><span style="width:10px;height:10px;border-radius:50%;background:var(--accent)"></span>Live</span>
    @endif
    <h1 style="font-size:clamp(28px,6vw,38px)">{{ $titel }}</h1>
    <p style="margin:0;color:#D1D5DB">{{ $text }}</p>
  </div>

  @if($anfrage->status !== 'abgelehnt')
  <div class="card">
    <ol style="list-style:none;margin:0;padding:0">
      @foreach($schritte as $schluessel => $bezeichnung)
        @php
            $index = $loop->index;
            $fertig = $aktuell !== false && $index < $aktuell;
            $jetzt = $aktuell === $index;
        @endphp
        <li style="display:flex;gap:14px">
          <div style="display:flex;flex-direction:column;align-items:center">
            <span aria-hidden="true" style="width:22px;height:22px;border-radius:50%;flex:0 0 22px;display:flex;align-items:center;justify-content:center;
              {{ $fertig ? 'background:var(--ink);' : ($jetzt ? 'background:var(--accent);' : 'border:2px solid #D6D3CC;') }}">
              @if($fertig)<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>@endif
            </span>
            @if(!$loop->last)<span style="width:2px;height:30px;background:{{ $fertig ? 'var(--ink)' : '#E4E1DA' }}"></span>@endif
          </div>
          <div style="padding-top:1px;display:flex;justify-content:space-between;gap:12px;flex:1;{{ $jetzt ? 'font-weight:600' : '' }}{{ (!$fertig && !$jetzt) ? 'color:var(--muted)' : '' }}">
            <span>{{ $bezeichnung }}@if($jetzt) <span class="sr-only" style="position:absolute;left:-9999px">(aktueller Stand)</span>@endif</span>
            @if($schluessel === 'neu')<span class="muted" style="font-size:13px;font-weight:400">{{ $anfrage->created_at->timezone('Europe/Berlin')->format('d.m.Y') }}</span>@endif
            @if($schluessel === 'termin' && $termin)<span class="muted" style="font-size:13px;font-weight:400">{{ $termin->format('d.m.Y, H:i') }} Uhr</span>@endif
          </div>
        </li>
      @endforeach
    </ol>
  </div>
  @endif

  <div class="card" style="display:flex;flex-direction:column;gap:8px">
    <span class="muted" style="font-size:13px;font-weight:600">Ihre Anfrage</span>
    <strong>{{ $anfrage->anliegenLabel() }}</strong>
    <span class="muted" style="white-space:pre-line">{{ \Illuminate\Support\Str::limit($anfrage->beschreibung, 300) }}</span>
  </div>

  <div style="display:flex;flex-wrap:wrap;gap:10px">
    @if($betrieb->telefon)
      <a class="btn btn-dark" style="flex:1" href="tel:{{ preg_replace('/[^0-9+]/', '', $betrieb->telefon) }}">{{ $betrieb->name }} anrufen</a>
    @endif
    <a class="btn" style="flex:1;background:#fff;color:var(--ink);border:1px solid var(--line)" href="{{ route('betrieb.website') }}">Zur Website</a>
  </div>
</section>
@endsection
