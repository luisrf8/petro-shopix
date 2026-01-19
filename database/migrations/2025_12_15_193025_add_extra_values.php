<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('background_image')->nullable()->after('description');
            $table->string('tiktok')->nullable()->after('background_image');
            $table->string('instagram')->nullable()->after('background_image');
            $table->string('facebook')->nullable()->after('background_image');

        });
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['background_image', 'tiktok', 'instagram', 'facebook']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
