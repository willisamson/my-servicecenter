<?php

/*
| Einstellungen der Plattform-Seiten (Startseite, Impressum, Datenschutz).
*/
return [
    // Kontaktadresse für Betriebe, die mitmachen wollen
    'email' => env('PORTAL_EMAIL', env('MAIL_FROM_ADDRESS')),

    // Abos jeden Morgen automatisch abrechnen und Rechnungen per Mail senden.
    // Nur auf der Live-Umgebung einschalten (RECHNUNGEN_AUTOMATISCH=true in der .env).
    'rechnungen_automatisch' => (bool) env('RECHNUNGEN_AUTOMATISCH', false),
];
