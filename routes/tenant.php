<?php

declare(strict_types=1);

use App\Http\Controllers\BetriebWebsiteController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
| Öffentliche Seiten eines Betriebs (Mini-Website, Anfrage, Status-Link).
| Erkannt wird der Betrieb an der Subdomain (heinz.my-servicecenter.com)
| oder später an einer eigenen Domain des Betriebs.
| Das Dashboard des Betriebs (/dashboard) kommt aus dem Filament-Panel "betrieb".
*/
Route::middleware([
    'web',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', [BetriebWebsiteController::class, 'show'])->name('betrieb.website');
    Route::post('/anfrage', [BetriebWebsiteController::class, 'anfrage'])
        ->middleware('throttle:5,1')
        ->name('betrieb.anfrage');
    Route::get('/status/{token}', [BetriebWebsiteController::class, 'status'])->name('betrieb.status');
});
