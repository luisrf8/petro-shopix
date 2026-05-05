<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('accounts_payables')) {
            Schema::create('accounts_payables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
                $table->string('document_number', 120)->nullable();
                $table->date('issued_at');
                $table->date('due_at')->nullable();
                $table->decimal('amount_total', 14, 4);
                $table->decimal('amount_paid', 14, 4)->default(0);
                $table->decimal('amount_pending', 14, 4);
                $table->string('currency_code', 3)->default('USD');
                $table->string('status', 20)->default('pending');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'provider_id']);
                $table->index(['tenant_id', 'due_at']);
                $table->unique(['tenant_id', 'purchase_order_id']);
            });
        }

        if (!Schema::hasTable('accounts_payable_payments')) {
            Schema::create('accounts_payable_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_payable_id')->constrained('accounts_payables')->cascadeOnDelete();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->date('paid_at');
                $table->decimal('amount', 14, 4);
                $table->string('currency_code', 3)->default('USD');
                $table->string('payment_method', 100)->nullable();
                $table->string('reference', 120)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'paid_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_payable_payments');
        Schema::dropIfExists('accounts_payables');
    }
};
