@extends('portal.layout')

@section('title', 'Impressum – my-servicecenter')

@section('content')
<section class="wrap" style="max-width:760px;padding-top:48px;display:flex;flex-direction:column;gap:20px">
  <h1 style="font-size:clamp(30px,4vw,44px)">Impressum</h1>
  {{-- TODO: Platzhalter in eckigen Klammern ersetzen (am besten mit einem Impressum-Generator oder anwaltlich prüfen lassen). --}}
  <div style="background:#fff;border:1px solid var(--line);border-radius:24px;padding:28px;line-height:1.7">
    <p style="margin-top:0"><strong>Angaben gemäß § 5 DDG</strong></p>
    <p>[Vor- und Nachname bzw. Firmenname und Rechtsform]<br>[Straße und Hausnummer]<br>[PLZ Ort]</p>
    <p><strong>Kontakt</strong><br>Telefon: [Telefonnummer]<br>E-Mail: [E-Mail-Adresse]</p>
    <p><strong>Umsatzsteuer-ID</strong><br>[USt-IdNr. gemäß § 27a UStG, falls vorhanden]</p>
    <p style="margin-bottom:0"><strong>Verantwortlich für den Inhalt</strong><br>[Name, Anschrift]</p>
  </div>
</section>
@endsection
