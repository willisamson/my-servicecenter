<!doctype html>
<html lang="de">
<body style="margin:0;padding:24px;background:#F5F4F0;font-family:Arial,Helvetica,sans-serif;color:#12161F">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #E4E1DA;border-radius:16px;padding:28px">
    <p style="margin:0 0 4px;font-size:13px;color:#9A3412;font-weight:bold">Neue Anfrage für {{ $betriebName }}</p>
    <h1 style="margin:0 0 20px;font-size:22px">{{ $anfrage->anliegenLabel() }}</h1>
    <table style="width:100%;border-collapse:collapse;font-size:15px" role="presentation">
      <tr><td style="padding:6px 0;color:#4B5563;width:130px">Kunde</td><td style="padding:6px 0">{{ $anfrage->name }}</td></tr>
      @if($anfrage->telefon)<tr><td style="padding:6px 0;color:#4B5563">Telefon</td><td style="padding:6px 0">{{ $anfrage->telefon }}</td></tr>@endif
      @if($anfrage->email)<tr><td style="padding:6px 0;color:#4B5563">E-Mail</td><td style="padding:6px 0">{{ $anfrage->email }}</td></tr>@endif
      <tr><td style="padding:6px 0;color:#4B5563">Ort</td><td style="padding:6px 0">{{ $anfrage->plz }}@if($anfrage->adresse), {{ $anfrage->adresse }}@endif</td></tr>
      @if($anfrage->wunschtermin)<tr><td style="padding:6px 0;color:#4B5563">Wunschtermin</td><td style="padding:6px 0">{{ $anfrage->wunschtermin }}</td></tr>@endif
    </table>
    <p style="margin:20px 0 6px;color:#4B5563;font-size:13px">Beschreibung</p>
    <p style="margin:0;white-space:pre-line;font-size:15px;line-height:1.6">{{ $anfrage->beschreibung }}</p>
    <p style="margin:28px 0 0"><a href="{{ $dashboardUrl }}" style="display:inline-block;background:#C2410C;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:10px">Anfrage im Dashboard öffnen</a></p>
  </div>
  <p style="max-width:560px;margin:16px auto 0;font-size:12px;color:#4B5563;text-align:center">Gesendet von my-servicecenter. Auf diese E-Mail antworten = Antwort an den Kunden (falls E-Mail angegeben).</p>
</body>
</html>
