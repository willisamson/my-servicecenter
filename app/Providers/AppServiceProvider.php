<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Uploads (Logos, Fotos) zuerst in einen festen, zentralen Zwischenspeicher.
        // Sonst landet die Datei bei Betrieben in einem anderen Ordner, als Livewire
        // sie danach sucht (die Mandanten-Trennung biegt "local" pro Betrieb um).
        config(['livewire.temporary_file_upload.disk' => 'uploads_tmp']);
    }
}
