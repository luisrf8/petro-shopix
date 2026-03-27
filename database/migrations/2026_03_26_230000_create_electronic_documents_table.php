<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronic_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider', 50)->default('thefactoryhka');
            $table->string('tipo_documento', 5)->nullable();
            $table->string('serie', 20)->nullable();
            $table->string('numero_documento', 20)->nullable();
            $table->string('numero_control', 50)->nullable();
            $table->string('transaccion_id', 100)->nullable();
            $table->string('estado_documento', 100)->nullable();
            $table->string('codigo', 30)->nullable();
            $table->text('mensaje')->nullable();
            $table->string('url_consulta', 250)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->boolean('is_annulled')->default(false);
            $table->timestamp('annulled_at')->nullable();
            $table->timestamps();

            $table->index(['sales_order_id', 'tipo_documento']);
            $table->index(['tenant_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_documents');
    }
};
