<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (!Schema::hasColumn('tenants', 'printer_tax_change_enabled')) {
                    $table->boolean('printer_tax_change_enabled')->default(false)->after('special_taxpayer');
                }

                if (!Schema::hasColumn('tenants', 'printer_tax_change_reference')) {
                    $table->string('printer_tax_change_reference')->nullable()->after('printer_tax_change_enabled');
                }
            });
        }

        if (!Schema::hasTable('sales_adjustment_notes')) {
            Schema::create('sales_adjustment_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
                $table->foreignId('electronic_document_id')->nullable()->constrained('electronic_documents')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('note_type', 20);
                $table->string('status', 30)->default('registered');
                $table->date('note_date');
                $table->string('internal_number', 60)->nullable();
                $table->string('document_code', 5)->nullable();
                $table->string('reference_document_number', 60)->nullable();
                $table->string('reference_control_number', 60)->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency_code', 3)->default('USD');
                $table->string('reason', 255);
                $table->text('notes')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('related_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'sales_order_id']);
                $table->index(['tenant_id', 'note_type']);
            });
        }

        if (!Schema::hasTable('sales_retentions')) {
            Schema::create('sales_retentions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
                $table->foreignId('electronic_document_id')->nullable()->constrained('electronic_documents')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('retention_type', 30)->default('iva');
                $table->string('status', 30)->default('registered');
                $table->date('retention_date');
                $table->string('certificate_number', 60)->nullable();
                $table->decimal('retention_rate', 8, 4)->default(0);
                $table->decimal('taxable_base', 12, 2);
                $table->decimal('retained_amount', 12, 2);
                $table->string('currency_code', 3)->default('USD');
                $table->text('notes')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'sales_order_id']);
                $table->index(['tenant_id', 'retention_type']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_retentions')) {
            Schema::dropIfExists('sales_retentions');
        }

        if (Schema::hasTable('sales_adjustment_notes')) {
            Schema::dropIfExists('sales_adjustment_notes');
        }

        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (Schema::hasColumn('tenants', 'printer_tax_change_reference')) {
                    $table->dropColumn('printer_tax_change_reference');
                }

                if (Schema::hasColumn('tenants', 'printer_tax_change_enabled')) {
                    $table->dropColumn('printer_tax_change_enabled');
                }
            });
        }
    }
};