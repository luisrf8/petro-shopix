<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Countries
        |--------------------------------------------------------------------------
        */
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | States
        |--------------------------------------------------------------------------
        */
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Cities
        |--------------------------------------------------------------------------
        */
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | DATA INSERT
        |--------------------------------------------------------------------------
        */

        // Country
        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Venezuela',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // States
        DB::table('states')->insert([
            ['id' => 1,  'country_id' => 1, 'name' => 'Amazonas'],
            ['id' => 2,  'country_id' => 1, 'name' => 'Anzoategui'],
            ['id' => 3,  'country_id' => 1, 'name' => 'Apure'],
            ['id' => 4,  'country_id' => 1, 'name' => 'Aragua'],
            ['id' => 5,  'country_id' => 1, 'name' => 'Barinas'],
            ['id' => 6,  'country_id' => 1, 'name' => 'Bolivar'],
            ['id' => 7,  'country_id' => 1, 'name' => 'Carabobo'],
            ['id' => 8,  'country_id' => 1, 'name' => 'Cojedes'],
            ['id' => 9,  'country_id' => 1, 'name' => 'Delta Amacuro'],
            ['id' => 10, 'country_id' => 1, 'name' => 'Distrito Capital'],
            ['id' => 11, 'country_id' => 1, 'name' => 'Falcon'],
            ['id' => 12, 'country_id' => 1, 'name' => 'Guarico'],
            ['id' => 13, 'country_id' => 1, 'name' => 'Lara'],
            ['id' => 14, 'country_id' => 1, 'name' => 'Merida'],
            ['id' => 15, 'country_id' => 1, 'name' => 'Miranda'],
            ['id' => 16, 'country_id' => 1, 'name' => 'Monagas'],
            ['id' => 17, 'country_id' => 1, 'name' => 'Nueva Esparta'],
            ['id' => 18, 'country_id' => 1, 'name' => 'Portuguesa'],
            ['id' => 19, 'country_id' => 1, 'name' => 'Sucre'],
            ['id' => 20, 'country_id' => 1, 'name' => 'Tachira'],
            ['id' => 21, 'country_id' => 1, 'name' => 'Trujillo'],
            ['id' => 22, 'country_id' => 1, 'name' => 'Vargas'],
            ['id' => 23, 'country_id' => 1, 'name' => 'Yaracuy'],
            ['id' => 24, 'country_id' => 1, 'name' => 'Zulia'],
        ]);

        // Cities
        DB::table('cities')->insert([
            // Amazonas
            ['state_id' => 1, 'name' => 'Puerto Ayacucho'],

            // Anzoátegui
            ['state_id' => 2, 'name' => 'Barcelona'],
            ['state_id' => 2, 'name' => 'Puerto La Cruz'],
            ['state_id' => 2, 'name' => 'Lechería'],
            ['state_id' => 2, 'name' => 'El Tigre'],
            ['state_id' => 2, 'name' => 'Anaco'],

            // Apure
            ['state_id' => 3, 'name' => 'San Fernando de Apure'],
            ['state_id' => 3, 'name' => 'Guasdualito'],

            // Aragua
            ['state_id' => 4, 'name' => 'Maracay'],
            ['state_id' => 4, 'name' => 'Turmero'],
            ['state_id' => 4, 'name' => 'La Victoria'],
            ['state_id' => 4, 'name' => 'Cagua'],

            // Barinas
            ['state_id' => 5, 'name' => 'Barinas'],

            // Bolívar
            ['state_id' => 6, 'name' => 'Ciudad Bolivar'],
            ['state_id' => 6, 'name' => 'Puerto Ordaz'],
            ['state_id' => 6, 'name' => 'San Felix'],
            ['state_id' => 6, 'name' => 'Upata'],

            // Carabobo
            ['state_id' => 7, 'name' => 'Valencia'],
            ['state_id' => 7, 'name' => 'Naguanagua'],
            ['state_id' => 7, 'name' => 'Puerto Cabello'],
            ['state_id' => 7, 'name' => 'Guacara'],

            // Cojedes
            ['state_id' => 8, 'name' => 'San Carlos'],
            ['state_id' => 8, 'name' => 'Tinaquillo'],

            // Delta Amacuro
            ['state_id' => 9, 'name' => 'Tucupita'],

            // Distrito Capital
            ['state_id' => 10, 'name' => 'Caracas'],

            // Falcón
            ['state_id' => 11, 'name' => 'Coro'],
            ['state_id' => 11, 'name' => 'Punto Fijo'],

            // Guárico
            ['state_id' => 12, 'name' => 'San Juan de los Morros'],
            ['state_id' => 12, 'name' => 'Calabozo'],

            // Lara
            ['state_id' => 13, 'name' => 'Barquisimeto'],
            ['state_id' => 13, 'name' => 'Cabudare'],
            ['state_id' => 13, 'name' => 'Carora'],

            // MMerida
            ['state_id' => 14, 'name' => 'Merida'],
            ['state_id' => 14, 'name' => 'El Vigía'],

            // Miranda
            ['state_id' => 15, 'name' => 'Los Teques'],
            ['state_id' => 15, 'name' => 'Guarenas'],
            ['state_id' => 15, 'name' => 'Guatire'],
            ['state_id' => 15, 'name' => 'Petare'],
            ['state_id' => 15, 'name' => 'Ocumare del Tuy'],

            // Monagas
            ['state_id' => 16, 'name' => 'Acosta'],
            ['state_id' => 16, 'name' => 'Aguasay'],
            ['state_id' => 16, 'name' => 'Bolivar'],
            ['state_id' => 16, 'name' => 'Caripe'],
            ['state_id' => 16, 'name' => 'Cedeno'],
            ['state_id' => 16, 'name' => 'Ezequiel Zamora'],
            ['state_id' => 16, 'name' => 'Libertador'],
            ['state_id' => 16, 'name' => 'Piar'],
            ['state_id' => 16, 'name' => 'Punceres'],
            ['state_id' => 16, 'name' => 'Santa Barbara'],
            ['state_id' => 16, 'name' => 'Sotillo'],
            ['state_id' => 16, 'name' => 'Uracoa'],


            // Nueva Esparta
            ['state_id' => 17, 'name' => 'La Asuncion'],
            ['state_id' => 17, 'name' => 'Porlamar'],
            ['state_id' => 17, 'name' => 'Juan Griego'],

            // Portuguesa
            ['state_id' => 18, 'name' => 'Guanare'],
            ['state_id' => 18, 'name' => 'Acarigua'],
            ['state_id' => 18, 'name' => 'Araure'],

            // Sucre
            ['state_id' => 19, 'name' => 'Cumana'],
            ['state_id' => 19, 'name' => 'Carupano'],

            // Táchira
            ['state_id' => 20, 'name' => 'San Cristóbal'],
            ['state_id' => 20, 'name' => 'Rubio'],
            ['state_id' => 20, 'name' => 'La Grita'],

            // Trujillo
            ['state_id' => 21, 'name' => 'Trujillo'],
            ['state_id' => 21, 'name' => 'Valera'],

            // Vargas
            ['state_id' => 22, 'name' => 'La Guaira'],

            // Yaracuy
            ['state_id' => 23, 'name' => 'San Felipe'],
            ['state_id' => 23, 'name' => 'Yaritagua'],

            // Zulia
            ['state_id' => 24, 'name' => 'Maracaibo'],
            ['state_id' => 24, 'name' => 'Cabimas'],
            ['state_id' => 24, 'name' => 'Ciudad Ojeda'],
            ['state_id' => 24, 'name' => 'Machiques'],
            ['state_id' => 24, 'name' => 'Santa Barbara del Zulia'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};
