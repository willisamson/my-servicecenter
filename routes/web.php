<?php

use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

/*
| Zentrale Routen (Portal) – nur auf den zentralen Domains erreichbar.
| Die Routen der Betriebe (Subdomains) stehen in routes/tenant.php.
*/
foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', [PortalController::class, 'start']);
        Route::get('/suche', [PortalController::class, 'suche']);
        Route::get('/sitemap.xml', [PortalController::class, 'sitemap']);
        Route::view('/impressum', 'portal.impressum');
        Route::view('/datenschutz', 'portal.datenschutz');

        // Branchen- und Ortsseiten, z. B. /friseur und /friseur/bad-homburg.
        // Reservierte Pfade (Admin, Livewire, Dateien …) sind ausgenommen.
        Route::get('/{branche}/{ort?}', [PortalController::class, 'branche'])
            ->where('branche', '(?!(?:admin|dashboard|livewire|filament|storage|build|up|suche|impressum|datenschutz|sitemap)\b)[a-z0-9-]+')
            ->where('ort', '[a-z0-9-]+');
    });
}
