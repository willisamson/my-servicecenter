@extends('betrieb.layout')

@section('title', $titel.' – '.$betrieb->name)

@section('content')
<section class="wrap" style="max-width:760px;padding-top:48px;display:flex;flex-direction:column;gap:20px">
  <h1 style="font-size:clamp(30px,4vw,44px)">{{ $titel }}</h1>
  @if(filled($text))
    <div class="card" style="white-space:pre-line;line-height:1.7">{{ $text }}</div>
  @else
    <div class="card muted">Die Angaben werden gerade ergänzt. Bei Fragen erreichen Sie {{ $betrieb->name }}@if($betrieb->telefon) unter {{ $betrieb->telefon }}@endif@if($betrieb->email) oder per E-Mail an {{ $betrieb->email }}@endif.</div>
  @endif
  <a href="{{ route('betrieb.website') }}">← Zurück zur Startseite</a>
</section>
@endsection
