<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Benutzer für einen Betrieb anlegen oder Passwort zurücksetzen, z. B.:
|   php artisan betrieb:benutzer heinz chef@heinz.de "Heinz Muster"
|   php artisan betrieb:benutzer heinz anna@heinz.de "Anna" --rolle=mitarbeiter
*/
Artisan::command('betrieb:benutzer {betrieb : Subdomain des Betriebs} {email} {name} {--rolle=inhaber : inhaber, buero oder mitarbeiter}', function (string $betrieb, string $email, string $name) {
    $tenant = \App\Models\Tenant::find($betrieb);

    if (! $tenant) {
        $this->error("Betrieb '{$betrieb}' nicht gefunden.");

        return 1;
    }

    $rolle = (string) $this->option('rolle');

    if (! array_key_exists($rolle, \App\Models\User::ROLLEN)) {
        $this->error('Rolle muss inhaber, buero oder mitarbeiter sein.');

        return 1;
    }

    $passwort = (string) $this->secret('Passwort (mindestens 8 Zeichen)');

    if (strlen($passwort) < 8) {
        $this->error('Das Passwort ist zu kurz.');

        return 1;
    }

    $tenant->run(function () use ($email, $name, $passwort, $rolle) {
        \App\Models\User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $passwort, 'rolle' => $rolle],
        );
    });

    $this->info("Benutzer {$email} ({$rolle}) für {$tenant->name} gespeichert.");

    return 0;
})->purpose('Legt einen Benutzer in der Datenbank eines Betriebs an');

/*
| Vorführ-Betrieb mit Beispieldaten anlegen, z. B.:
|   php artisan betrieb:demo deine@email.de
|   php artisan betrieb:demo deine@email.de --neu   (vorhandenen Demo-Betrieb löschen und neu anlegen)
| Danach erreichbar unter demo.<zentrale Domain>, Login unter /dashboard.
*/
Artisan::command('betrieb:demo {email : Login-E-Mail des Inhabers (bekommt auch die Anfrage-Mails)} {--neu : Vorhandenen Demo-Betrieb löschen und neu anlegen}', function (string $email) {
    $vorhanden = \App\Models\Tenant::find('demo');

    if ($vorhanden && ! $this->option('neu')) {
        $this->error('Der Demo-Betrieb existiert schon. Mit --neu wird er gelöscht und neu angelegt.');

        return 1;
    }

    if ($vorhanden) {
        $this->warn('Lösche den bisherigen Demo-Betrieb samt Datenbank …');
        $vorhanden->delete();
    }

    $this->info('Lege den Demo-Betrieb an (Datenbank + Tabellen) …');

    $betrieb = \App\Models\Tenant::create([
        'id' => 'demo',
        'name' => 'Muster Heiztechnik',
        'branche' => 'heizung',
        'ort' => 'Musterstadt',
        'email' => $email,
        'aktiv' => true,
        'strasse' => 'Hauptstraße 12',
        'plz' => '12345',
        'telefon' => '0123 456789',
        'beschreibung' => "Ihr Fachbetrieb für Heizung, Sanitär und Wärmepumpen in Musterstadt und Umgebung.\nWir warten, reparieren und modernisieren – schnell, sauber und zu fairen Preisen.",
        'leistungen' => ['Heizungswartung', 'Reparatur & Notdienst', 'Heizungstausch', 'Wärmepumpen', 'Bad & Sanitär', 'Förderberatung'],
        'oeffnungszeiten' => "Mo–Do 7:30–17:00 Uhr\nFr 7:30–14:00 Uhr\nNotdienst rund um die Uhr",
        'notdienst' => true,
        'impressum' => "Muster Heiztechnik (fiktiver Vorführ-Betrieb)\nHauptstraße 12, 12345 Musterstadt\n\nDies ist ein Demo-Betrieb von my-servicecenter. Alle Angaben sind erfunden.",
        'datenschutz' => 'Vorführ-Betrieb von my-servicecenter – hier steht später die Datenschutzerklärung des Betriebs.',
    ]);

    $betrieb->domains()->create(['domain' => 'demo']);

    $passwort = \Illuminate\Support\Str::password(12, true, true, false);

    $betrieb->run(function () use ($email, $passwort) {
        $berlin = 'Europe/Berlin';

        \App\Models\User::create(['name' => 'Max Muster', 'email' => $email, 'password' => $passwort, 'rolle' => 'inhaber']);
        \App\Models\User::create(['name' => 'Anna Büro', 'email' => 'buero@demo.test', 'password' => $passwort, 'rolle' => 'buero']);
        \App\Models\User::create(['name' => 'Tom Monteur', 'email' => 'monteur@demo.test', 'password' => $passwort, 'rolle' => 'mitarbeiter']);

        $beispiele = [
            ['status' => 'neu', 'anliegen' => 'stoerung', 'name' => 'Petra Schmidt', 'telefon' => '0123 111222', 'plz' => '12345', 'adresse' => 'Lindenweg 4',
                'beschreibung' => "Heizung zeigt Fehler F28 und bleibt kalt.\nWarmwasser geht auch nicht.", 'wunschtermin' => 'so schnell wie möglich', 'vor' => now()->subMinutes(25)],
            ['status' => 'neu', 'anliegen' => 'waermepumpe', 'name' => 'Familie Becker', 'email' => 'becker@example.com', 'plz' => '12347',
                'beschreibung' => 'Einfamilienhaus Baujahr 1998, aktuell Gasheizung. Lohnt sich eine Wärmepumpe und wie läuft das mit der Förderung?', 'wunschtermin' => 'Beratungstermin nächste Woche', 'vor' => now()->subHours(3)],
            ['status' => 'angenommen', 'anliegen' => 'wartung', 'name' => 'Jonas Weber', 'telefon' => '0123 333444', 'plz' => '12345',
                'beschreibung' => 'Jährliche Wartung der Gastherme ist fällig.', 'wunschtermin' => 'vormittags', 'vor' => now()->subDay()],
            ['status' => 'termin', 'anliegen' => 'stoerung', 'name' => 'Sabine Kraus', 'telefon' => '0123 555666', 'plz' => '12349', 'adresse' => 'Am Markt 7',
                'beschreibung' => 'Die Heizkörper im Obergeschoss werden nicht mehr richtig warm.', 'termin_am' => now($berlin)->addDay()->setTime(9, 0)->utc(), 'vor' => now()->subDays(2)],
            ['status' => 'unterwegs', 'anliegen' => 'sonstiges', 'name' => 'Michael Hoffmann', 'telefon' => '0123 777888', 'plz' => '12345',
                'beschreibung' => 'Austausch von drei Thermostatventilen im Erdgeschoss.', 'termin_am' => now($berlin)->setTime(14, 0)->utc(), 'vor' => now()->subDays(4)],
            ['status' => 'erledigt', 'anliegen' => 'wartung', 'name' => 'Klaus Meyer', 'email' => 'meyer@example.com', 'plz' => '12346',
                'beschreibung' => 'Wartung Ölheizung inkl. Abgasmessung.', 'termin_am' => now($berlin)->subDays(3)->setTime(10, 30)->utc(), 'notiz_intern' => 'Brennerdüse getauscht, Rechnung raus.', 'vor' => now()->subDays(6)],
        ];

        foreach ($beispiele as $beispiel) {
            $zeit = $beispiel['vor'];
            unset($beispiel['vor']);

            $anfrage = \App\Models\Anfrage::create($beispiel);
            $anfrage->forceFill(['created_at' => $zeit, 'updated_at' => $zeit])->save();
        }
    });

    $central = config('tenancy.central_domains')[0];

    $this->newLine();
    $this->info('Fertig! Demo-Betrieb "Muster Heiztechnik":');
    $this->line("  Website:    demo.{$central}");
    $this->line("  Dashboard:  demo.{$central}/dashboard");
    $this->line("  Inhaber:    {$email}");
    $this->line('  Büro:       buero@demo.test');
    $this->line('  Monteur:    monteur@demo.test');
    $this->line("  Passwort (für alle drei): {$passwort}");

    return 0;
})->purpose('Legt den Vorführ-Betrieb "demo" mit Beispieldaten an');
