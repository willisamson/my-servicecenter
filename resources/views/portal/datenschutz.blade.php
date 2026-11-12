@extends('portal.layout')

@section('title', 'Datenschutz – my-servicecenter')

@section('content')
<section class="wrap" style="max-width:760px;padding-top:48px;display:flex;flex-direction:column;gap:20px">
  <h1 style="font-size:clamp(30px,4vw,44px)">Datenschutzerklärung</h1>
  {{-- Der Text kommt aus Admin → Einstellungen → Rechtliches. --}}
  <div style="background:#fff;border:1px solid var(--line);border-radius:24px;padding:28px;line-height:1.7;white-space:pre-line">@if($text = \App\Models\Einstellung::wert('datenschutz')){{ $text }}@else<span style="color:#9A3412">[Die Datenschutzerklärung wird gerade ergänzt.]</span>@endif</div>
</section>
@endsection
