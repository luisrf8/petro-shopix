<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('webpush.database_connection', env('WEBPUSH_DB_CONNECTION', env('DB_CONNECTION', 'mysql')));
        $tableName = config('webpush.table_name', env('WEBPUSH_DB_TABLE', 'push_subscriptions'));

        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('subscribable');
            $table->string('endpoint', 500)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $connection = config('webpush.database_connection', env('WEBPUSH_DB_CONNECTION', env('DB_CONNECTION', 'mysql')));
        $tableName = config('webpush.table_name', env('WEBPUSH_DB_TABLE', 'push_subscriptions'));

        Schema::connection($connection)->dropIfExists($tableName);
    }
};