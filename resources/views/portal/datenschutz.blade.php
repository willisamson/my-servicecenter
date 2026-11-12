@extends('portal.layout')

@section('title', 'Datenschutz – my-servicecenter')

@section('content')
<section class="wrap" style="max-width:760px;padding-top:48px;display:flex;flex-direction:column;gap:20px">
  <h1 style="font-size:clamp(30px,4vw,44px)">Datenschutzerklärung</h1>
  {{-- TODO: Durch eine vollständige Datenschutzerklärung ersetzen (Generator oder Anwalt). --}}
  <div style="background:#fff;border:1px solid var(--line);border-radius:24px;padding:28px;line-height:1.7">
    <p style="margin-top:0">[Hier steht die Datenschutzerklärung von my-servicecenter. Sie sollte u. a. abdecken: Verantwortlicher, Hosting in Deutschland (IONOS), Server-Logfiles, Anfrageformulare der Betriebe, E-Mail-Versand, Google Fonts, Rechte der Betroffenen.]</p>
  </div>
</section>
@endsection
