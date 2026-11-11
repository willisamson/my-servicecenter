<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Anfragen von Endkunden – liegen in der Datenbank des Betriebs.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anfragen', function (Blueprint $table) {
            $table->id();
            $table->string('status_token', 64)->unique(); // geheimer Teil des Status-Links
            $table->string('status')->default('neu')->index();
            $table->string('anliegen');
            $table->text('beschreibung');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('telefon')->nullable();
            $table->string('plz', 10);
            $table->string('adresse')->nullable();
            $table->string('wunschtermin')->nullable();
            $table->dateTime('termin_am')->nullable();
            $table->text('notiz_intern')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anfragen');
    }
};
