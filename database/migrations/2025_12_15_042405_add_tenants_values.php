<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slogan')->nullable()->after('color_accent');
            $table->text('description')->nullable()->after('slogan');
            $table->string('address')->nullable()->after('description');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');   // Precisión suficiente para coordenadas
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['slogan', 'description', 'address', 'latitude', 'longitude']);
        });
    }
};
