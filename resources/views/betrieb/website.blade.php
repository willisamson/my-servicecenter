@extends('betrieb.layout')

@push('styles')
<style>
.hero{display:flex;flex-wrap:wrap;gap:40px;align-items:flex-start;padding-top:48px;padding-bottom:56px}
.hero-text{flex:1 1 520px;min-width:0;display:flex;flex-direction:column;gap:22px}
.hero h1{font-size:clamp(36px,4.6vw,58px)}
.anfrage{flex:1 1 360px;min-width:0;max-width:460px;display:flex;flex-direction:column;gap:16px}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.chip{position:relative}
.chip input{position:absolute;opacity:0;width:1px;height:1px}
.chip span{display:inline-flex;align-items:center;min-height:44px;padding:0 14px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--ink);font-size:14px;font-weight:500;cursor:pointer}
.chip input:checked+span{background:var(--ink);border-color:var(--ink);color:#fff}
.chip input:focus-visible+span{outline:2px solid var(--accent);outline-offset:2px}
.reihe{display:flex;flex-wrap:wrap;gap:12px}.reihe>label{flex:1 1 150px}
.check{flex-direction:row;align-items:flex-start;gap:10px;font-weight:500}
.check input{width:20px;height:20px;margin-top:2px;flex:0 0 20px}
.hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.band{background:var(--card);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.band .wrap{padding-top:64px;padding-bottom:64px;display:flex;flex-direction:column;gap:28px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}
.kachel{background:var(--bg);border-radius:20px;padding:22px;font-family:'Space Grotesk',sans-serif;font-size:19px;font-weight:600}
.kontakt{display:flex;flex-wrap:wrap;gap:32px;padding-top:64px}
.kontakt>div{flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:12px}
</style>
@endpush

@section('nav')
<nav class="nav" aria-label="Seitennavigation">
  @if(!empty($betrieb->leistungen))<a href="#leistungen">Leistungen</a>@endif
  <a href="#kontakt">Kontakt</a>
  <a class="btn" href="#anfrage" style="min-height:42px;border-radius:999px">Anfrage senden</a>
</nav>
@endsection

@section('content')
<section class="wrap hero">
  <div class="hero-text">
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      <span class="pill">{{ $betrieb->brancheLabel() }}</span>
      @if($betrieb->ort)<span class="pill">{{ $betrieb->ort }} und Umgebung</span>@endif
      @if($betrieb->notdienst)<span class="pill pill-accent">Notdienst verfügbar</span>@endif
    </div>
    <h1>{{ $betrieb->name }}</h1>
    <p class="muted" style="margin:0;font-size:18px;max-width:600px;white-space:pre-line">{{ $betrieb->beschreibung ?: 'Ihr Fachbetrieb in der Region. Schildern Sie uns Ihr Anliegen – wir melden uns schnell bei Ihnen.' }}</p>
    @if($betrieb->telefon)
      <p style="margin:0;font-size:17px">Lieber direkt anrufen? <a href="tel:{{ preg_replace('/[^0-9+]/', '', $betrieb->telefon) }}" style="font-weight:600">{{ $betrieb->telefon }}</a></p>
    @endif
  </div>

  <form id="anfrage" class="card anfrage" method="POST" action="{{ route('betrieb.anfrage') }}" aria-label="Anfrage an {{ $betrieb->name }}">
    @csrf
    <div>
      <h2 style="font-size:26px">Was ist los?</h2>
      <p class="muted" style="margin:6px 0 0;font-size:14px">Ihre Anfrage geht nur an {{ $betrieb->name }}.</p>
    </div>

    <fieldset style="border:0;margin:0;padding:0">
      <legend style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:8px">Anliegen</legend>
      <div class="chips">
        @foreach($anliegen as $schluessel => $text)
          <label class="chip"><input type="radio" name="anliegen" value="{{ $schluessel }}" @checked(old('anliegen', array_key_first($anliegen)) === $schluessel)><span>{{ $text }}</span></label>
        @endforeach
      </div>
      @error('anliegen')<span class="fehler">{{ $message }}</span>@enderror
    </fieldset>

    <label>Beschreibung
      <textarea name="beschreibung" rows="3" placeholder="Was genau ist passiert? Gerät, Fehlercode, seit wann …" required>{{ old('beschreibung') }}</textarea>
      @error('beschreibung')<span class="fehler">{{ $message }}</span>@enderror
    </label>

    <label>Ihr Name
      <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>
      @error('name')<span class="fehler">{{ $message }}</span>@enderror
    </label>

    <div class="reihe">
      <label>Telefon
        <input type="tel" name="telefon" value="{{ old('telefon') }}" autocomplete="tel">
        @error('telefon')<span class="fehler">{{ $message }}</span>@enderror
      </label>
      <label>E-Mail
        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email">
        @error('email')<span class="fehler">{{ $message }}</span>@enderror
      </label>
    </div>

    <div class="reihe">
      <label>PLZ
        <input type="text" name="plz" value="{{ old('plz') }}" autocomplete="postal-code" inputmode="numeric" required>
        @error('plz')<span class="fehler">{{ $message }}</span>@enderror
      </label>
      <label>Wunschtermin
        <input type="text" name="wunschtermin" value="{{ old('wunschtermin') }}" placeholder="z. B. nächste Woche vormittags">
      </label>
    </div>

    <label>Straße und Hausnummer (optional)
      <input type="text" name="adresse" value="{{ old('adresse') }}" autocomplete="street-address">
    </label>

    <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <label class="check"><input type="checkbox" name="datenschutz" value="1" @checked(old('datenschutz')) required><span>Ich bin einverstanden, dass meine Angaben zur Bearbeitung der Anfrage gespeichert werden (<a href="#">Datenschutz</a>).</span></label>
    @error('datenschutz')<span class="fehler">{{ $message }}</span>@enderror

    <button type="submit" class="btn">Anfrage senden</button>
    <p class="muted" style="margin:0;font-size:13px;text-align:center">Kein Konto nötig. Den Stand Ihrer Anfrage sehen Sie anschließend über einen persönlichen Link.</p>
  </form>
</section>

@if(!empty($betrieb->leistungen))
<section id="leistungen" class="band">
  <div class="wrap">
    <h2 style="font-size:clamp(28px,3.2vw,40px)">Leistungen</h2>
    <div class="grid">
      @foreach($betrieb->leistungen as $leistung)
        <div class="kachel">{{ $leistung }}</div>
      @endforeach
    </div>
  </div>
</section>
@endif

<section id="kontakt" class="wrap kontakt">
  <div>
    <h2 style="font-size:clamp(28px,3.2vw,40px)">Kontakt</h2>
    @if($betrieb->strasse || $betrieb->plz || $betrieb->ort)
      <span><strong>Adresse:</strong> {{ $betrieb->strasse }}@if($betrieb->strasse), @endif{{ $betrieb->plz }} {{ $betrieb->ort }}</span>
    @endif
    @if($betrieb->telefon)<span><strong>Telefon:</strong> {{ $betrieb->telefon }}</span>@endif
    @if($betrieb->email)<span><strong>E-Mail:</strong> <a href="mailto:{{ $betrieb->email }}">{{ $betrieb->email }}</a></span>@endif
  </div>
  @if($betrieb->oeffnungszeiten)
  <div>
    <h3 style="font-size:22px">Öffnungszeiten</h3>
    <p style="margin:0;white-space:pre-line">{{ $betrieb->oeffnungszeiten }}</p>
  </div>
  @endif
</section>
@endsection
