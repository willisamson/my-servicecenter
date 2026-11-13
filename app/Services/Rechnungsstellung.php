<?php

namespace App\Services;

use App\Mail\RechnungMail;
use App\Models\Abo;
use App\Models\Einstellung;
use App\Models\Rechnung;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Erstellt, speichert, versendet und storniert Rechnungen an die Betriebe.
 */
class Rechnungsstellung
{
    /** Pflichtangaben auf deinen Rechnungen (§ 14 UStG), die in den Einstellungen stehen müssen. */
    public const PFLICHTFELDER = [
        'firmenname' => 'Firmenname',
        'strasse' => 'Straße',
        'plz' => 'PLZ',
        'ort' => 'Ort',
    ];

    /** Was fehlt noch, damit Rechnungen erstellt werden dürfen? Leeres Array = alles da. */
    public static function fehlendeAngaben(): array
    {
        $fehlt = [];

        foreach (self::PFLICHTFELDER as $feld => $label) {
            if (blank(Einstellung::wert($feld))) {
                $fehlt[] = $label;
            }
        }

        if (blank(Einstellung::wert('steuernummer')) && blank(Einstellung::wert('ust_id'))) {
            $fehlt[] = 'Steuernummer oder USt-IdNr.';
        }

        if (blank(Einstellung::wert('iban'))) {
            $fehlt[] = 'IBAN';
        }

        return $fehlt;
    }

    /** Rechnung für den nächsten Zeitraum eines Abos erstellen und das Abo weiterschalten. */
    public function fuerAbo(Abo $abo): Rechnung
    {
        $abo->loadMissing('tarif', 'betrieb');

        $von = $abo->naechste_rechnung_am?->copy() ?? today();
        $bis = $abo->zeitraumEnde($von);

        $tarifName = $abo->tarif?->name ?? 'Abo';
        $monate = $abo->intervall_monate ?: 1;

        $positionen = [[
            'bezeichnung' => 'my-servicecenter – Tarif „'.$tarifName.'“',
            'beschreibung' => 'Leistungszeitraum '.$von->format('d.m.Y').' – '.$bis->format('d.m.Y'),
            'menge' => $monate,
            'einheit' => $monate === 1 ? 'Monat' : 'Monate',
            'einzelpreis' => round($abo->monatspreis(), 2),
        ]];

        $rechnung = $this->erstellen(
            tenantId: $abo->tenant_id,
            empfaenger: $this->empfaengerAusAbo($abo),
            positionen: $positionen,
            leistungVon: $von,
            leistungBis: $bis,
            email: $abo->rechnung_email ?: $abo->betrieb?->email,
            aboId: $abo->id,
        );

        $abo->update(['naechste_rechnung_am' => $bis->copy()->addDay()]);

        return $rechnung;
    }

    /**
     * Neue Rechnung mit fortlaufender Nummer anlegen und das PDF ablegen.
     */
    public function erstellen(
        ?string $tenantId,
        array $empfaenger,
        array $positionen,
        ?Carbon $leistungVon = null,
        ?Carbon $leistungBis = null,
        ?string $email = null,
        ?int $aboId = null,
        string $art = 'rechnung',
        ?int $stornoVonId = null,
    ): Rechnung {
        if ($fehlt = self::fehlendeAngaben()) {
            throw new RuntimeException('Für Rechnungen fehlen in den Einstellungen noch: '.implode(', ', $fehlt).'.');
        }

        $summen = self::summen($positionen);
        $tage = (int) Einstellung::wert('zahlungsziel_tage', 14);

        return (new Rechnung)->getConnection()->transaction(function () use ($tenantId, $empfaenger, $positionen, $leistungVon, $leistungBis, $email, $aboId, $art, $stornoVonId, $summen, $tage) {
            $rechnung = Rechnung::create([
                'nummer' => $this->naechsteNummer(),
                'art' => $art,
                'tenant_id' => $tenantId,
                'abo_id' => $aboId,
                'storno_von_id' => $stornoVonId,
                'datum' => today(),
                'leistung_von' => $leistungVon,
                'leistung_bis' => $leistungBis,
                'faellig_am' => $art === 'storno' ? null : today()->addDays($tage),
                'absender' => self::absender(),
                'empfaenger' => $empfaenger,
                'positionen' => $positionen,
                'netto' => $summen['netto'],
                'ust_satz' => $summen['ust_satz'],
                'ust' => $summen['ust'],
                'brutto' => $summen['brutto'],
                'status' => $art === 'storno' ? 'storniert' : 'offen',
                'empfaenger_email' => $email,
            ]);

            $pfad = $rechnung->datum->format('Y').'/'.$rechnung->nummer.'.pdf';
            Storage::disk('rechnungen')->put($pfad, $this->pdf($rechnung));
            $rechnung->update(['pdf_pfad' => $pfad]);

            return $rechnung;
        });
    }

    /** Stornorechnung mit negativen Beträgen; das Original wird als storniert markiert. */
    public function stornieren(Rechnung $original): Rechnung
    {
        if ($original->istStorno() || $original->status === 'storniert') {
            throw new RuntimeException('Diese Rechnung ist bereits storniert.');
        }

        $positionen = array_map(fn (array $p) => array_merge($p, [
            'einzelpreis' => -1 * (float) $p['einzelpreis'],
        ]), $original->positionen);

        $positionen[0]['beschreibung'] = trim(($positionen[0]['beschreibung'] ?? '').' · Storno zu Rechnung '.$original->nummer.' vom '.$original->datum->format('d.m.Y'));

        $storno = $this->erstellen(
            tenantId: $original->tenant_id,
            empfaenger: $original->empfaenger,
            positionen: $positionen,
            leistungVon: $original->leistung_von,
            leistungBis: $original->leistung_bis,
            email: $original->empfaenger_email,
            aboId: $original->abo_id,
            art: 'storno',
            stornoVonId: $original->id,
        );

        $original->update(['status' => 'storniert']);

        return $storno;
    }

    /** Rechnung per E-Mail mit PDF-Anhang verschicken. */
    public function versenden(Rechnung $rechnung): bool
    {
        if (blank($rechnung->empfaenger_email) || blank($rechnung->pdf_pfad)) {
            return false;
        }

        Mail::to($rechnung->empfaenger_email)->send(new RechnungMail($rechnung));
        $rechnung->update(['versendet_am' => now()]);

        return true;
    }

    /** PDF erzeugen (für neue Rechnungen und die Layout-Vorschau). */
    public function pdf(Rechnung $rechnung): string
    {
        return Pdf::loadView('rechnungen.pdf', [
            'r' => $rechnung,
            'layout' => self::layout(),
        ])->setPaper('a4')->output();
    }

    /** Beispielrechnung für die Vorschau – wird nicht gespeichert. */
    public function vorschau(): string
    {
        $positionen = [[
            'bezeichnung' => 'my-servicecenter – Tarif „Start“',
            'beschreibung' => 'Leistungszeitraum '.today()->format('d.m.Y').' – '.today()->addMonthNoOverflow()->subDay()->format('d.m.Y'),
            'menge' => 1,
            'einheit' => 'Monat',
            'einzelpreis' => 49.00,
        ]];

        $rechnung = new Rechnung(array_merge([
            'nummer' => self::praefix().'-'.today()->format('Y').'-0001',
            'art' => 'rechnung',
            'tenant_id' => 'musterbetrieb',
            'datum' => today(),
            'leistung_von' => today(),
            'leistung_bis' => today()->addMonthNoOverflow()->subDay(),
            'faellig_am' => today()->addDays((int) Einstellung::wert('zahlungsziel_tage', 14)),
            'absender' => self::absender(),
            'empfaenger' => ['zeilen' => ['Muster Heiztechnik GmbH', 'Max Muster', 'Musterstraße 1', '12345 Musterstadt']],
            'positionen' => $positionen,
            'status' => 'offen',
        ], self::summen($positionen)));

        return $this->pdf($rechnung);
    }

    public static function summen(array $positionen): array
    {
        $netto = round(array_sum(array_map(
            fn (array $p) => round((float) $p['menge'] * (float) $p['einzelpreis'], 2),
            $positionen,
        )), 2);

        $satz = Einstellung::wert('kleinunternehmer') ? 0.0 : (float) Einstellung::wert('ust_satz', 19);
        $ust = round($netto * $satz / 100, 2);

        return ['netto' => $netto, 'ust_satz' => $satz, 'ust' => $ust, 'brutto' => round($netto + $ust, 2)];
    }

    /** Deine Firmendaten zum Zeitpunkt der Rechnung (bleiben auf alten Rechnungen unverändert). */
    public static function absender(): array
    {
        $felder = [
            'firmenname', 'vertreten_durch', 'strasse', 'plz', 'ort', 'telefon', 'email', 'register',
            'ust_id', 'steuernummer', 'bank_inhaber', 'bank_name', 'iban', 'bic', 'kleinunternehmer',
        ];

        $absender = [];
        foreach ($felder as $feld) {
            $absender[$feld] = Einstellung::wert($feld);
        }

        return $absender;
    }

    /** Aussehen der Rechnung (Logo, Farbe, Texte) – immer der aktuelle Stand. */
    public static function layout(): array
    {
        $logo = null;
        $pfad = Einstellung::wert('rechnung_logo') ?: Einstellung::wert('logo');

        if ($pfad && Storage::disk('public')->exists($pfad)) {
            $mime = Storage::disk('public')->mimeType($pfad) ?: 'image/png';
            $logo = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($pfad));
        }

        $farbe = (string) Einstellung::wert('rechnung_farbe', '#C2410C');

        return [
            'logo' => $logo,
            'farbe' => preg_match('/^#[0-9a-fA-F]{6}$/', $farbe) ? $farbe : '#C2410C',
            'einleitung' => Einstellung::wert('rechnung_einleitung', 'vielen Dank für Ihr Vertrauen. Für unsere Leistungen berechnen wir Ihnen:'),
            'schluss' => Einstellung::wert('rechnung_schluss', 'Bei Fragen zur Rechnung erreichen Sie uns jederzeit per E-Mail.'),
        ];
    }

    protected function empfaengerAusAbo(Abo $abo): array
    {
        if (filled($abo->rechnung_adresse)) {
            $zeilen = array_values(array_filter(array_map('trim', preg_split('/\R/', $abo->rechnung_adresse))));
        } else {
            $b = $abo->betrieb;
            $zeilen = array_values(array_filter([
                $b?->name,
                $b?->strasse,
                trim(($b?->plz ?? '').' '.($b?->ort ?? '')),
            ]));
        }

        return ['zeilen' => $zeilen, 'kundennummer' => $abo->tenant_id];
    }

    protected static function praefix(): string
    {
        $praefix = preg_replace('/[^A-Za-z0-9]/', '', (string) Einstellung::wert('rechnung_praefix', 'RE'));

        return $praefix !== '' ? $praefix : 'RE';
    }

    /**
     * Fortlaufende Nummer je Jahr, z. B. RE-2026-0001.
     * Die Sperre auf den Einstellungs-Datensatz verhindert doppelte Nummern bei gleichzeitigen Läufen.
     */
    protected function naechsteNummer(): string
    {
        Einstellung::query()->whereKey(Einstellung::aktuell()->getKey())->lockForUpdate()->first();

        $basis = self::praefix().'-'.today()->format('Y').'-';

        $letzte = Rechnung::query()
            ->where('nummer', 'like', $basis.'%')
            ->pluck('nummer')
            ->map(fn (string $n) => (int) substr($n, strlen($basis)))
            ->max() ?? 0;

        return $basis.str_pad((string) ($letzte + 1), 4, '0', STR_PAD_LEFT);
    }
}
