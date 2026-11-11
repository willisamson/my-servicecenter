<?php

namespace App\Http\Controllers;

use App\Models\Anfrage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        return redirect()
            ->route('betrieb.status', $anfrage->status_token)
            ->with('neu', true);
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
