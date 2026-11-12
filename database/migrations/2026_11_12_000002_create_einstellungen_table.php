<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Einstellungen der Plattform (Firmendaten, Logo, Rechtliches) – eine Zeile.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('einstellungen', function (Blueprint $table) {
            $table->id();
            $table->json('daten')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('einstellungen');
    }
};
