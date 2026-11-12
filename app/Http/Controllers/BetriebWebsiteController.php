<?php

namespace App\Http\Controllers;

use App\Mail\AnfrageBestaetigung;
use App\Mail\NeueAnfrageAnBetrieb;
use App\Models\Anfrage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Öffentliche Seiten eines Betriebs: Mini-Website, Anfrageformular, Status-Seite.
 */
class BetriebWebsiteController extends Controller
{
    public function show()
    {
        $betrieb = tenant();
        abort_unless($betrieb->aktiv, 404);

        return view('betrieb.website', [
            'betrieb' => $betrieb,
            'anliegen' => Anfrage::anliegenFuer($betrieb->branche),
        ]);
    }

    public function anfrage(Request $request)
    {
        $betrieb = tenant();
        abort_unless($betrieb->aktiv, 404);

        // Spam-Schutz: unsichtbares Feld, das nur Bots ausfüllen.
        if (filled($request->input('website'))) {
            return redirect()->route('betrieb.website');
        }

        $daten = $request->validate([
            'anliegen' => ['required', Rule::in(array_keys(Anfrage::anliegenFuer($betrieb->branche)))],
            'beschreibung' => ['required', 'string', 'max:3000'],
            'name' => ['required', 'string', 'max:255'],
            'telefon' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:telefon'],
            'plz' => ['required', 'string', 'max:10'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'wunschtermin' => ['nullable', 'string', 'max:255'],
            'datenschutz' => ['accepted'],
        ], [
            'required' => 'Bitte füllen Sie dieses Feld aus.',
            'required_without' => 'Bitte geben Sie Telefon oder E-Mail an.',
            'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'accepted' => 'Bitte stimmen Sie der Datenschutzerklärung zu.',
            'max' => 'Die Eingabe ist zu lang.',
            'in' => 'Bitte wählen Sie ein Anliegen aus.',
        ]);

        unset($daten['datenschutz']);

        $anfrage = Anfrage::create($daten);

        $this->benachrichtigen($anfrage);

        return redirect()
            ->route('betrieb.status', $anfrage->status_token)
            ->with('neu', true);
    }

    /**
     * E-Mail an den Betrieb und – falls angegeben – Bestätigung an den Kunden.
     * Ein Mail-Fehler darf die Anfrage nicht verhindern, deshalb nur protokollieren.
     */
    protected function benachrichtigen(Anfrage $anfrage): void
    {
        $betrieb = tenant();

        try {
            $empfaenger = $betrieb->email ?: User::where('rolle', 'inhaber')->pluck('email')->all();

            if (! empty($empfaenger)) {
                Mail::to($empfaenger)->send(new NeueAnfrageAnBetrieb($anfrage, $betrieb->name, url('/dashboard/anfragen/'.$anfrage->getKey().'/edit')));
            }

            if ($anfrage->email) {
                Mail::to($anfrage->email)->send(new AnfrageBestaetigung($anfrage, $betrieb->name, $betrieb->email, $betrieb->telefon));
            }
        } catch (Throwable $fehler) {
            report($fehler);
        }
    }

    public function impressum()
    {
        return view('betrieb.rechtliches', [
            'betrieb' => tenant(),
            'titel' => 'Impressum',
            'text' => tenant('impressum'),
        ]);
    }

    public function datenschutz()
    {
        return view('betrieb.rechtliches', [
            'betrieb' => tenant(),
            'titel' => 'Datenschutzerklärung',
            'text' => tenant('datenschutz'),
        ]);
    }

    public function status(string $token)
    {
        $anfrage = Anfrage::where('status_token', $token)->firstOrFail();

        return view('betrieb.status', [
            'betrieb' => tenant(),
            'anfrage' => $anfrage,
        ]);
    }
}
