<?php

namespace App\Http\Controllers;

use App\Models\Branche;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Öffentliche Seiten des Portals: Startseite mit Suche, Trefferliste,
 * Branchen- und Ortsseiten (z. B. /friseur/bad-homburg) und die Sitemap.
 *
 * Gezeigt werden nur Betriebe mit "aktiv" und "im_portal".
 */
class PortalController extends Controller
{
    /** Höchstzahl Treffer bei der freien Suche. */
    protected const MAX_TREFFER = 50;

    public function start()
    {
        $anzahl = Tenant::query()->imPortal()->toBase()
            ->selectRaw('branche, count(*) as anzahl')
            ->groupBy('branche')
            ->pluck('anzahl', 'branche');

        $branchen = collect(Branche::optionen())
            ->map(fn (string $name, string $slug) => ['slug' => $slug, 'name' => $name, 'anzahl' => (int) ($anzahl[$slug] ?? 0)])
            ->values();

        return view('portal.start', [
            'branchen' => $branchen,
            'gesamt' => $anzahl->sum(),
        ]);
    }

    public function suche(Request $request)
    {
        $brancheSlug = $request->query('branche');
        $ort = $request->query('ort');

        $branche = is_string($brancheSlug) && $brancheSlug !== '' ? Branche::finde($brancheSlug) : null;
        $branche = $branche?->aktiv ? $branche : null;
        $ort = is_string($ort) ? Str::limit(trim($ort), 100, '') : '';

        // Nur eine Branche gewählt → auf die saubere Branchenseite.
        if ($branche && $ort === '') {
            return redirect('/'.$branche->slug);
        }

        // Ort passt genau auf eine Ortsseite → dorthin (gute, teilbare Adresse).
        if ($branche && $ort !== '' && ! preg_match('/^\d/', $ort)) {
            $ortSlug = Str::slug($ort, '-', 'de');
            $gibtEs = Tenant::query()->imPortal()->where('branche', $branche->slug)->get()
                ->contains(fn (Tenant $t) => $t->ortSlug() === $ortSlug);

            if ($gibtEs) {
                return redirect('/'.$branche->slug.'/'.$ortSlug);
            }
        }

        $query = Tenant::query()->imPortal()->orderByDesc('data->notdienst')->orderBy('name');

        if ($branche) {
            $query->where('branche', $branche->slug);
        }

        if ($ort !== '') {
            $muster = $this->like($ort);
            $query->where(function (Builder $w) use ($muster) {
                $w->where('ort', 'like', '%'.$muster.'%')
                    ->orWhere('data->plz', 'like', $muster.'%');
            });
        }

        $betriebe = $query->limit(self::MAX_TREFFER + 1)->get();

        $titel = ($branche?->name ?? 'Fachbetriebe').($ort !== '' ? ' in '.$ort : '');

        return view('portal.liste', [
            'titel' => $titel,
            'untertitel' => null,
            'betriebe' => $betriebe->take(self::MAX_TREFFER),
            'mehr' => $betriebe->count() > self::MAX_TREFFER,
            'branche' => $branche,
            'suchOrt' => $ort,
            'orte' => collect(),
            'indexieren' => false,
            'kanonisch' => null,
        ]);
    }

    /** /friseur oder /friseur/bad-homburg */
    public function branche(string $slug, ?string $ortSlug = null)
    {
        $branche = Branche::finde($slug);
        abort_unless($branche && $branche->aktiv, 404);

        $alle = Tenant::query()->imPortal()
            ->where('branche', $branche->slug)
            ->orderByDesc('data->notdienst')
            ->orderBy('name')
            ->get();

        $orte = $this->orte($alle);
        $betriebe = $alle;
        $ortName = null;

        if ($ortSlug !== null) {
            abort_unless($orte->has($ortSlug), 404);
            $ortName = $orte[$ortSlug]['name'];
            $betriebe = $alle->filter(fn (Tenant $t) => $t->ortSlug() === $ortSlug)->values();
        }

        $titel = $branche->name.($ortName ? ' in '.$ortName : '');
        $anzahl = $betriebe->count();

        return view('portal.liste', [
            'titel' => $titel,
            'untertitel' => $anzahl === 1 ? '1 Betrieb' : $anzahl.' Betriebe',
            'betriebe' => $betriebe,
            'mehr' => false,
            'branche' => $branche,
            'suchOrt' => $ortName ?? '',
            'orte' => $ortSlug === null ? $orte : collect(),
            'indexieren' => $anzahl > 0,
            'kanonisch' => url('/'.$branche->slug.($ortSlug ? '/'.$ortSlug : '')),
        ]);
    }

    public function sitemap()
    {
        $basis = request()->getSchemeAndHttpHost();
        $urls = [$basis.'/'];

        $betriebe = Tenant::query()->imPortal()->get();

        foreach ($betriebe->groupBy('branche') as $slug => $gruppe) {
            $branche = Branche::finde($slug);
            if (! $branche || ! $branche->aktiv) {
                continue;
            }

            $urls[] = $basis.'/'.$slug;

            foreach ($this->orte($gruppe)->keys() as $ortSlug) {
                $urls[] = $basis.'/'.$slug.'/'.$ortSlug;
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url).'</loc></url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Orte der Betriebe: slug => ['name' => 'Bad Homburg', 'anzahl' => 3], nach Name sortiert.
     */
    protected function orte(Collection $betriebe): Collection
    {
        return $betriebe
            ->filter(fn (Tenant $t) => $t->ortSlug() !== '')
            ->groupBy(fn (Tenant $t) => $t->ortSlug())
            ->map(fn (Collection $g) => ['name' => trim((string) $g->first()->ort), 'anzahl' => $g->count()])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
    }

    /** Sonderzeichen für LIKE entschärfen. */
    protected function like(string $text): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);
    }
}
