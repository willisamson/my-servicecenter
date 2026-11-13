<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abrechnung der Plattform: Tarife, Abos der Betriebe, Rechnungen.
 * Alles in der zentralen Datenbank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarife', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('beschreibung')->nullable();
            $table->decimal('preis_netto', 10, 2); // pro Monat
            $table->boolean('aktiv')->default(true);
            $table->unsignedInteger('sortierung')->default(0);
            $table->timestamps();
        });

        Schema::create('abos', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->unique(); // ein Abo je Betrieb
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('tarif_id')->constrained('tarife')->restrictOnDelete();
            $table->decimal('preis_netto', 10, 2)->nullable(); // abweichender Preis, sonst Tarifpreis
            $table->unsignedTinyInteger('intervall_monate')->default(1);
            $table->date('start_am');
            $table->date('kostenlos_bis')->nullable(); // Pilot- / Testphase
            $table->date('naechste_rechnung_am')->nullable();
            $table->date('gekuendigt_zum')->nullable();
            $table->string('rechnung_email')->nullable();
            $table->text('rechnung_adresse')->nullable(); // abweichende Rechnungsanschrift
            $table->text('notiz')->nullable();
            $table->timestamps();
        });

        Schema::create('rechnungen', function (Blueprint $table) {
            $table->id();
            $table->string('nummer')->unique();
            $table->string('art')->default('rechnung'); // rechnung | storno
            $table->string('tenant_id')->nullable()->index(); // bleibt erhalten, auch wenn der Betrieb gelöscht wird
            $table->foreignId('abo_id')->nullable()->constrained('abos')->nullOnDelete();
            $table->foreignId('storno_von_id')->nullable()->constrained('rechnungen')->nullOnDelete();
            $table->date('datum');
            $table->date('leistung_von')->nullable();
            $table->date('leistung_bis')->nullable();
            $table->date('faellig_am')->nullable();
            $table->json('absender');   // Momentaufnahme deiner Firmendaten
            $table->json('empfaenger'); // Momentaufnahme der Rechnungsanschrift
            $table->json('positionen');
            $table->decimal('netto', 10, 2);
            $table->decimal('ust_satz', 5, 2);
            $table->decimal('ust', 10, 2);
            $table->decimal('brutto', 10, 2);
            $table->string('status')->default('offen')->index(); // offen | bezahlt | storniert
            $table->date('bezahlt_am')->nullable();
            $table->string('empfaenger_email')->nullable();
            $table->dateTime('versendet_am')->nullable();
            $table->string('pdf_pfad')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rechnungen');
        Schema::dropIfExists('abos');
        Schema::dropIfExists('tarife');
    }
};
