@php
    use App\Models\Rechnung;
    $a = $r->absender ?? [];
    $e = $r->empfaenger ?? [];
    $farbe = $layout['farbe'];
    $eur = fn ($b) => Rechnung::euro($b);
    $absenderZeile = collect([$a['firmenname'] ?? null, $a['strasse'] ?? null, trim(($a['plz'] ?? '').' '.($a['ort'] ?? ''))])->filter()->implode(' · ');
    $ustSatz = rtrim(rtrim(number_format((float) $r->ust_satz, 2, ',', ''), '0'), ',');
@endphp
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>{{ $r->titel() }} {{ $r->nummer }}</title>
<style>
@page { margin: 18mm 18mm 34mm 22mm; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #12161F; line-height: 1.45; }
.kopf { width: 100%; border-collapse: collapse; }
.kopf td { vertical-align: top; padding: 0; }
.logo { max-height: 18mm; max-width: 70mm; }
.firma { font-size: 13pt; font-weight: bold; color: {{ $farbe }}; }
.absenderzeile { font-size: 7pt; color: #6B7280; border-bottom: 0.5pt solid #9CA3AF; padding-bottom: 1mm; margin-bottom: 2mm; }
.anschrift { margin-top: 14mm; width: 85mm; min-height: 32mm; }
.infos { width: 100%; border-collapse: collapse; }
.infos td { padding: 0.6mm 0; font-size: 9pt; }
.infos .label { color: #6B7280; padding-right: 4mm; }
h1 { font-size: 16pt; margin: 10mm 0 4mm; color: #12161F; }
.balken { height: 1.2mm; background: {{ $farbe }}; width: 22mm; margin-bottom: 6mm; }
.pos { width: 100%; border-collapse: collapse; margin-top: 4mm; }
.pos th { text-align: left; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.4pt; color: #FFFFFF; background: {{ $farbe }}; padding: 2.2mm 2mm; }
.pos td { padding: 2.6mm 2mm; border-bottom: 0.5pt solid #E4E1DA; vertical-align: top; }
.r { text-align: right; }
.klein { font-size: 8pt; color: #6B7280; }
.summen { width: 75mm; margin-left: auto; border-collapse: collapse; margin-top: 3mm; }
.summen td { padding: 1.4mm 2mm; }
.summen .gesamt td { font-weight: bold; font-size: 11pt; border-top: 1pt solid #12161F; padding-top: 2.4mm; }
.hinweis { margin-top: 8mm; }
.fuss { position: fixed; bottom: -26mm; left: 0; right: 0; height: 22mm; font-size: 7pt; color: #6B7280; border-top: 0.5pt solid #D1D5DB; padding-top: 2mm; }
.fuss table { width: 100%; border-collapse: collapse; }
.fuss td { vertical-align: top; width: 33%; padding-right: 3mm; }
.stempel { color: #B91C1C; font-weight: bold; }
</style>
</head>
<body>

<div class="fuss">
  <table><tr>
    <td>
      <strong>{{ $a['firmenname'] ?? '' }}</strong><br>
      @if(! empty($a['vertreten_durch'])) Inhaber/GF: {{ $a['vertreten_durch'] }}<br>@endif
      {{ $a['strasse'] ?? '' }}<br>{{ trim(($a['plz'] ?? '').' '.($a['ort'] ?? '')) }}
    </td>
    <td>
      @if(! empty($a['telefon'])) Tel. {{ $a['telefon'] }}<br>@endif
      @if(! empty($a['email'])) {{ $a['email'] }}<br>@endif
      @if(! empty($a['steuernummer'])) St.-Nr. {{ $a['steuernummer'] }}<br>@endif
      @if(! empty($a['ust_id'])) USt-IdNr. {{ $a['ust_id'] }}<br>@endif
      @if(! empty($a['register'])) {{ $a['register'] }}@endif
    </td>
    <td>
      @if(! empty($a['bank_name'])) {{ $a['bank_name'] }}<br>@endif
      @if(! empty($a['bank_inhaber'])) Inhaber: {{ $a['bank_inhaber'] }}<br>@endif
      @if(! empty($a['iban'])) IBAN {{ $a['iban'] }}<br>@endif
      @if(! empty($a['bic'])) BIC {{ $a['bic'] }}@endif
    </td>
  </tr></table>
</div>

<table class="kopf"><tr>
  <td>
    @if($layout['logo'])
      <img class="logo" src="{{ $layout['logo'] }}" alt="">
    @else
      <div class="firma">{{ $a['firmenname'] ?? 'my-servicecenter' }}</div>
    @endif
  </td>
  <td style="width:62mm">
    <table class="infos">
      <tr><td class="label">{{ $r->titel() }} Nr.</td><td class="r"><strong>{{ $r->nummer }}</strong></td></tr>
      <tr><td class="label">Datum</td><td class="r">{{ $r->datum?->format('d.m.Y') }}</td></tr>
      @if(! empty($e['kundennummer']))<tr><td class="label">Kundennummer</td><td class="r">{{ $e['kundennummer'] }}</td></tr>@endif
      @if($r->leistung_von && $r->leistung_bis && $r->leistung_von->isSameDay($r->leistung_bis))
        <tr><td class="label">Leistungsdatum</td><td class="r">{{ $r->leistung_von->format('d.m.Y') }}</td></tr>
      @elseif($r->leistung_von && $r->leistung_bis)
        <tr><td class="label">Leistungszeitraum</td><td class="r">{{ $r->leistung_von->format('d.m.Y') }} – {{ $r->leistung_bis->format('d.m.Y') }}</td></tr>
      @endif
      @if($r->faellig_am)<tr><td class="label">Fällig am</td><td class="r">{{ $r->faellig_am->format('d.m.Y') }}</td></tr>@endif
    </table>
  </td>
</tr></table>

<div class="anschrift">
  <div class="absenderzeile">{{ $absenderZeile }}</div>
  @foreach(($e['zeilen'] ?? []) as $zeile)
    {{ $zeile }}<br>
  @endforeach
</div>

<h1>{{ $r->titel() }} {{ $r->nummer }}</h1>
<div class="balken"></div>

@if($r->istStorno() && $r->stornoVon)
  <p>Hiermit stornieren wir die Rechnung <strong>{{ $r->stornoVon->nummer }}</strong> vom {{ $r->stornoVon->datum->format('d.m.Y') }} vollständig.</p>
@else
  <p>Sehr geehrte Damen und Herren,<br>{{ $layout['einleitung'] }}</p>
@endif

<table class="pos">
  <thead><tr>
    <th style="width:8mm">Pos.</th>
    <th>Leistung</th>
    <th class="r" style="width:22mm">Menge</th>
    <th class="r" style="width:26mm">Einzelpreis</th>
    <th class="r" style="width:26mm">Gesamt</th>
  </tr></thead>
  <tbody>
  @foreach($r->positionen as $i => $p)
    <tr>
      <td>{{ $i + 1 }}</td>
      <td><strong>{{ $p['bezeichnung'] }}</strong>@if(! empty($p['beschreibung']))<br><span class="klein">{{ $p['beschreibung'] }}</span>@endif</td>
      <td class="r">{{ rtrim(rtrim(number_format((float) $p['menge'], 2, ',', '.'), '0'), ',') }} {{ $p['einheit'] ?? '' }}</td>
      <td class="r">{{ $eur($p['einzelpreis']) }}</td>
      <td class="r">{{ $eur(round((float) $p['menge'] * (float) $p['einzelpreis'], 2)) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

<table class="summen">
  <tr><td>Summe netto</td><td class="r">{{ $eur($r->netto) }}</td></tr>
  @if((float) $r->ust_satz > 0)
    <tr><td>zzgl. {{ $ustSatz }} % USt.</td><td class="r">{{ $eur($r->ust) }}</td></tr>
  @endif
  <tr class="gesamt"><td>{{ $r->istStorno() ? 'Gutschrift' : 'Rechnungsbetrag' }}</td><td class="r">{{ $eur($r->brutto) }}</td></tr>
</table>

<div class="hinweis">
  @if(! empty($a['kleinunternehmer']))
    <p>Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.</p>
  @endif
  @if($r->istStorno())
    <p>Bereits gezahlte Beträge erstatten wir auf Ihr Konto.</p>
  @elseif($r->status === 'bezahlt')
    <p class="stempel">Bezahlt{{ $r->bezahlt_am ? ' am '.$r->bezahlt_am->format('d.m.Y') : '' }} – vielen Dank.</p>
  @else
    <p>Bitte überweisen Sie den Rechnungsbetrag bis zum <strong>{{ $r->faellig_am?->format('d.m.Y') }}</strong>
      @if(! empty($a['iban'])) auf das Konto IBAN {{ $a['iban'] }}@endif
      unter Angabe der Rechnungsnummer <strong>{{ $r->nummer }}</strong>.</p>
  @endif
  <p>{{ $layout['schluss'] }}</p>
  <p>Mit freundlichen Grüßen<br>{{ $a['vertreten_durch'] ?: ($a['firmenname'] ?? '') }}</p>
</div>

</body>
</html>
