<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Mitarbeiter-Funktionen (Datenbank des Betriebs):
| Zuweisung und Termin an der Anfrage, Notizen/Fotos, Material, Stempeluhr.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anfragen', function (Blueprint $table) {
            $table->foreignId('mitarbeiter_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('termin_dauer')->default(60)->after('termin_am'); // Minuten
            $table->dateTime('erledigt_am')->nullable()->after('termin_dauer');
            $table->index('termin_am');
        });

        Schema::create('auftrag_notizen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anfrage_id')->constrained('anfragen')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('text')->nullable();
            $table->json('fotos')->nullable();
            $table->timestamps();
        });

        Schema::create('auftrag_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anfrage_id')->constrained('anfragen')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bezeichnung');
            $table->decimal('menge', 10, 2)->default(1);
            $table->string('einheit', 20)->default('Stk');
            $table->timestamps();
        });

        Schema::create('arbeitszeiten', function (Blueprint $table) {
            $table->id();
            // restrict: Arbeitszeiten müssen aufbewahrt werden – Benutzer mit Zeiten nicht löschen.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('anfrage_id')->nullable()->constrained('anfragen')->nullOnDelete();
            $table->dateTime('beginn');
            $table->dateTime('ende')->nullable();
            $table->unsignedInteger('minuten')->nullable();
            $table->string('notiz')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'beginn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arbeitszeiten');
        Schema::dropIfExists('auftrag_material');
        Schema::dropIfExists('auftrag_notizen');

        Schema::table('anfragen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mitarbeiter_id');
            $table->dropIndex(['termin_am']);
            $table->dropColumn(['termin_dauer', 'erledigt_am']);
        });
    }
};
