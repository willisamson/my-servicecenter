<!doctype html>
<html lang="de">
<body style="margin:0;padding:24px;background:#F5F4F0;font-family:Arial,Helvetica,sans-serif;color:#12161F">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #E4E1DA;border-radius:16px;padding:28px">
    <h1 style="margin:0 0 12px;font-size:22px">Danke für Ihre Anfrage, {{ $anfrage->name }}!</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6">{{ $betriebName }} hat Ihre Anfrage „{{ $anfrage->anliegenLabel() }}“ erhalten und meldet sich in Kürze bei Ihnen.</p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.6">Den aktuellen Stand – Terminbestätigung, Anfahrt des Monteurs, Erledigung – sehen Sie jederzeit hier:</p>
    <p style="margin:0 0 24px"><a href="{{ $anfrage->status_link }}" style="display:inline-block;background:#C2410C;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:10px">Status meiner Anfrage ansehen</a></p>
    @if($betriebTelefon)
      <p style="margin:0;font-size:14px;color:#4B5563">Dringend? Rufen Sie direkt an: {{ $betriebTelefon }}</p>
    @endif
  </div>
  <p style="max-width:560px;margin:16px auto 0;font-size:12px;color:#4B5563;text-align:center">Diese E-Mail wurde über my-servicecenter im Auftrag von {{ $betriebName }} versendet.</p>
</body>
</html>
