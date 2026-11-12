<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Branchen (Kategorien) der Plattform – im Admin-Bereich pflegbar.
| Jede Branche bringt ihre eigenen Anliegen für das Anfrageformular mit.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branchen', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->json('anliegen')->nullable();
            $table->boolean('aktiv')->default(true);
            $table->unsignedInteger('sortierung')->default(0);
            $table->timestamps();
        });

        $jetzt = now();
        $standard = [
            ['heizung', 'Heizung & Sanitär', ['stoerung' => 'Heizung heizt nicht / Störung', 'wartung' => 'Wartung', 'tausch' => 'Heizungstausch', 'waermepumpe' => 'Wärmepumpe', 'sonstiges' => 'Sonstiges']],
            ['garten', 'Garten- & Landschaftsbau', ['pflege' => 'Gartenpflege', 'neuanlage' => 'Neuanlage / Umgestaltung', 'schnitt' => 'Baum- und Heckenschnitt', 'sonstiges' => 'Sonstiges']],
            ['floristik', 'Floristik', ['strauss' => 'Blumenstrauß', 'anlass' => 'Hochzeit / Feier', 'trauer' => 'Trauerfloristik', 'sonstiges' => 'Sonstiges']],
            ['friseur', 'Friseur', ['schnitt' => 'Haarschnitt', 'farbe' => 'Färben / Strähnen', 'anlass' => 'Hochzeit / Anlass', 'sonstiges' => 'Sonstiges']],
        ];

        foreach ($standard as $i => [$slug, $name, $anliegen]) {
            DB::table('branchen')->insert([
                'slug' => $slug,
                'name' => $name,
                'anliegen' => json_encode($anliegen, JSON_UNESCAPED_UNICODE),
                'aktiv' => true,
                'sortierung' => ($i + 1) * 10,
                'created_at' => $jetzt,
                'updated_at' => $jetzt,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branchen');
    }
};
