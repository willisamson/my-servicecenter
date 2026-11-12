{{-- Suchfeld: Branche + Ort/PLZ. Erwartet optional $gewaehlt (Branchen-Slug) und $ortWert. --}}
<form class="suche" action="/suche" method="get" role="search">
  <label for="such-branche">Branche</label>
  <select id="such-branche" name="branche">
    <option value="">Alle Branchen</option>
    @foreach(\App\Models\Branche::optionen() as $slug => $name)
      <option value="{{ $slug }}" @selected(($gewaehlt ?? null) === $slug)>{{ $name }}</option>
    @endforeach
  </select>
  <label for="such-ort">Ort oder PLZ</label>
  <input id="such-ort" name="ort" type="text" placeholder="Ort oder PLZ" value="{{ $ortWert ?? '' }}" maxlength="100" autocomplete="address-level2">
  <button class="btn" type="submit">Finden</button>
</form>
