<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
| Routen der Betriebe.
| Erkannt wird der Betrieb an der Subdomain (musterbetrieb.my-servicecenter.com)
| oder später an einer eigenen Domain des Betriebs.
*/
Route::middleware([
    'web',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Vorläufige Testseite – wird später die Mini-Website des Betriebs.
    Route::get('/', function () {
        return 'Betrieb: '.e(tenant('name')).' | Datenbank: '.e(DB::connection()->getDatabaseName());
    });
});
