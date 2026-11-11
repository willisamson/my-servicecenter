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
