@extends('portal.layout')

@section('title', 'Impressum – my-servicecenter')

@php
    $e = fn (string $feld) => \App\Models\Einstellung::wert($feld);
    $fehlt = '<span style="color:#9A3412">[noch nicht hinterlegt]</span>';
@endphp

@section('content')
<section class="wrap" style="max-width:760px;padding-top:48px;display:flex;flex-direction:column;gap:20px">
  <h1 style="font-size:clamp(30px,4vw,44px)">Impressum</h1>
  {{-- Die Angaben kommen aus Admin → Einstellungen. --}}
  <div style="background:#fff;border:1px solid var(--line);border-radius:24px;padding:28px;line-height:1.7">
    <p style="margin-top:0"><strong>Angaben gemäß § 5 DDG</strong></p>
    <p>
      @if($e('firmenname')){{ $e('firmenname') }}@else{!! $fehlt !!}@endif<br>
      @if($e('strasse')){{ $e('strasse') }}@else{!! $fehlt !!}@endif<br>
      {{ $e('plz') }} {{ $e('ort') }}
    </p>
    @if($e('vertreten_durch'))<p><strong>Vertreten durch</strong><br>{{ $e('vertreten_durch') }}</p>@endif
    <p><strong>Kontakt</strong><br>
      Telefon: @if($e('telefon')){{ $e('telefon') }}@else{!! $fehlt !!}@endif<br>
      E-Mail: @if($e('email')){{ $e('email') }}@else{!! $fehlt !!}@endif
    </p>
    @if($e('register'))<p><strong>Registereintrag</strong><br>{{ $e('register') }}</p>@endif
    @if($e('ust_id'))<p><strong>Umsatzsteuer-ID</strong><br>Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG: {{ $e('ust_id') }}</p>@endif
    @if($e('impressum_zusatz'))<p style="white-space:pre-line;margin-bottom:0">{{ $e('impressum_zusatz') }}</p>@endif
  </div>
</section>
@endsection
