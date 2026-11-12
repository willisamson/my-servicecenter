<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schalter "Im Portal anzeigen": Nur Betriebe mit diesem Häkchen erscheinen
 * in der Suche auf der Startseite. Standard aus – ein neuer Betrieb wird erst
 * sichtbar, wenn Profil, Impressum und Datenschutz fertig sind.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('im_portal')->default(false)->after('aktiv');
            $table->index(['aktiv', 'im_portal', 'branche']);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['aktiv', 'im_portal', 'branche']);
            $table->dropColumn('im_portal');
        });
    }
};
