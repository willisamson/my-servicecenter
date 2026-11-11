<?php

use Illuminate\Support\Facades\Route;

/*
| Zentrale Routen (Portal) – nur auf den zentralen Domains erreichbar.
| Die Routen der Betriebe (Subdomains) stehen in routes/tenant.php.
*/
foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            return view('welcome');
        });
    });
}
