<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('providers')) {
            Schema::table('providers', function (Blueprint $table) {
                if (!Schema::hasColumn('providers', 'rif')) {
                    $table->string('rif', 20)->nullable()->after('name');
                }

                if (!Schema::hasColumn('providers', 'fiscal_person_type')) {
                    $table->string('fiscal_person_type', 20)->default('pj')->after('rif');
                }

                if (!Schema::hasColumn('providers', 'fiscal_residency_type')) {
                    $table->string('fiscal_residency_type', 30)->default('domiciliado')->after('fiscal_person_type');
                }

                if (!Schema::hasColumn('providers', 'is_special_taxpayer')) {
                    $table->boolean('is_special_taxpayer')->default(false)->after('fiscal_residency_type');
                }
            });
        }

        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (!Schema::hasColumn('tenants', 'tax_unit_value')) {
                    $table->decimal('tax_unit_value', 12, 4)->default(0);
                }
            });
        }

        if (Schema::hasTable('accounts_payables')) {
            Schema::table('accounts_payables', function (Blueprint $table) {
                if (!Schema::hasColumn('accounts_payables', 'is_service')) {
                    $table->boolean('is_service')->default(true)->after('currency_code');
                }

                if (!Schema::hasColumn('accounts_payables', 'taxable_base')) {
                    $table->decimal('taxable_base', 14, 4)->nullable()->after('is_service');
                }

                if (!Schema::hasColumn('accounts_payables', 'tax_rate')) {
                    $table->decimal('tax_rate', 8, 4)->nullable()->after('taxable_base');
                }

                if (!Schema::hasColumn('accounts_payables', 'tax_amount')) {
                    $table->decimal('tax_amount', 14, 4)->nullable()->after('tax_rate');
                }

                if (!Schema::hasColumn('accounts_payables', 'islr_concept_code')) {
                    $table->string('islr_concept_code', 40)->nullable()->after('tax_amount');
                }
            });
        }

        if (!Schema::hasTable('islr_withholding_concepts')) {
            Schema::create('islr_withholding_concepts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->string('code', 40);
                $table->string('name', 160);
                $table->decimal('rate_percent', 8, 4);
                $table->decimal('sustraendo_ut', 12, 4)->default(0);
                $table->string('applicable_person_type', 20)->default('any');
                $table->string('applicable_residency_type', 30)->default('any');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tenant_id', 'is_active']);
                $table->unique(['tenant_id', 'code']);
            });
        }

        if (!Schema::hasTable('purchase_vat_retentions')) {
            Schema::create('purchase_vat_retentions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
                $table->foreignId('account_payable_id')->nullable()->constrained('accounts_payables')->nullOnDelete();
                $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('retention_date');
                $table->date('legal_deadline_at')->nullable();
                $table->boolean('issued_within_deadline')->default(true);
                $table->string('certificate_number', 60)->nullable();
                $table->string('invoice_number', 60)->nullable();
                $table->string('control_number', 60)->nullable();
                $table->decimal('retention_rate', 8, 4)->default(75);
                $table->decimal('taxable_base', 14, 4);
                $table->decimal('tax_amount', 14, 4);
                $table->decimal('retained_amount', 14, 4);
                $table->string('currency_code', 3)->default('USD');
                $table->string('status', 20)->default('issued');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'retention_date']);
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('islr_withholdings')) {
            Schema::create('islr_withholdings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('account_payable_id')->nullable()->constrained('accounts_payables')->nullOnDelete();
                $table->foreignId('account_payable_payment_id')->nullable()->constrained('accounts_payable_payments')->nullOnDelete();
                $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
                $table->foreignId('concept_id')->nullable()->constrained('islr_withholding_concepts')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('retention_date');
                $table->date('payment_date')->nullable();
                $table->string('certificate_number', 60)->nullable();
                $table->decimal('base_amount', 14, 4);
                $table->decimal('rate_percent', 8, 4);
                $table->decimal('sustraendo_ut', 12, 4)->default(0);
                $table->decimal('sustraendo_amount', 14, 4)->default(0);
                $table->decimal('retained_amount', 14, 4);
                $table->string('currency_code', 3)->default('USD');
                $table->string('status', 20)->default('issued');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'retention_date']);
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('islr_withholdings');
        Schema::dropIfExists('purchase_vat_retentions');
        Schema::dropIfExists('islr_withholding_concepts');

        if (Schema::hasTable('accounts_payables')) {
            Schema::table('accounts_payables', function (Blueprint $table) {
                foreach (['islr_concept_code', 'tax_amount', 'tax_rate', 'taxable_base', 'is_service'] as $column) {
                    if (Schema::hasColumn('accounts_payables', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'tax_unit_value')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('tax_unit_value');
            });
        }

        if (Schema::hasTable('providers')) {
            Schema::table('providers', function (Blueprint $table) {
                foreach (['is_special_taxpayer', 'fiscal_residency_type', 'fiscal_person_type', 'rif'] as $column) {
                    if (Schema::hasColumn('providers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
