@php($r = $rechnung)
<!doctype html>
<html lang="de">
<body style="margin:0;padding:24px;background:#F5F4F0;font-family:Arial,Helvetica,sans-serif;color:#12161F">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #E4E1DA;border-radius:16px;padding:28px">
    <h1 style="margin:0 0 12px;font-size:20px">{{ $r->titel() }} {{ $r->nummer }}</h1>
    <p style="margin:0 0 14px;font-size:15px;line-height:1.6">Guten Tag,</p>
    @if($text)
      <p style="margin:0 0 14px;font-size:15px;line-height:1.6">{!! nl2br(e($text)) !!}</p>
    @else
      <p style="margin:0 0 14px;font-size:15px;line-height:1.6">anbei erhalten Sie Ihre {{ $r->titel() }} für my-servicecenter als PDF.</p>
    @endif
    <table style="width:100%;border-collapse:collapse;font-size:15px;margin:0 0 18px">
      <tr><td style="padding:6px 0;color:#4B5563">Betrag</td><td style="padding:6px 0;text-align:right;font-weight:bold">{{ \App\Models\Rechnung::euro($r->brutto) }}</td></tr>
      @if($r->faellig_am)
        <tr><td style="padding:6px 0;color:#4B5563">Fällig am</td><td style="padding:6px 0;text-align:right">{{ $r->faellig_am->format('d.m.Y') }}</td></tr>
      @endif
      @if($r->leistung_von && $r->leistung_bis)
        <tr><td style="padding:6px 0;color:#4B5563">Zeitraum</td><td style="padding:6px 0;text-align:right">{{ $r->leistung_von->format('d.m.Y') }} – {{ $r->leistung_bis->format('d.m.Y') }}</td></tr>
      @endif
    </table>
    <p style="margin:0;font-size:15px;line-height:1.6">Viele Grüße<br>{{ $firma }}</p>
  </div>
</body>
</html>
