<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_employment_profiles')) {
            Schema::create('user_employment_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('employment_type', 50)->nullable();
                $table->string('contract_file_path', 255)->nullable();
                $table->unsignedSmallInteger('family_dependents')->default(0);
                $table->date('hired_at')->nullable();
                $table->date('birth_date')->nullable();
                $table->unsignedTinyInteger('age')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_employment_profiles');
    }
};
