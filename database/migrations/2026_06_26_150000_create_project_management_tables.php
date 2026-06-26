<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pm_quotations')) {
            Schema::create('pm_quotations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('type', 30)->default('customer');
                $table->string('status', 30)->default('draft');
                $table->string('title', 255);
                $table->string('customer_name', 255)->nullable();
                $table->string('customer_email', 255)->nullable();
                $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
                $table->string('provider_name', 255)->nullable();
                $table->decimal('discount_percent', 7, 4)->default(0);
                $table->decimal('subtotal', 14, 4)->default(0);
                $table->decimal('discount_amount', 14, 4)->default(0);
                $table->decimal('total_amount', 14, 4)->default(0);
                $table->string('currency_code', 3)->default('USD');
                $table->date('valid_until')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'type']);
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('pm_projects')) {
            Schema::create('pm_projects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->string('phase', 30)->default('inicio');
                $table->date('starts_at')->nullable();
                $table->date('development_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->decimal('budget_amount', 14, 4)->default(0);
                $table->string('currency_code', 3)->default('USD');
                $table->foreignId('quotation_id')->nullable()->constrained('pm_quotations')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'phase']);
                $table->index(['tenant_id', 'starts_at']);
            });
        }

        if (!Schema::hasTable('pm_quotation_items')) {
            Schema::create('pm_quotation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained('pm_quotations')->cascadeOnDelete();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
                $table->string('service_name', 255)->nullable();
                $table->string('description', 255);
                $table->decimal('quantity', 14, 4)->default(1);
                $table->decimal('unit_price', 14, 4)->default(0);
                $table->decimal('discount_percent', 7, 4)->default(0);
                $table->decimal('total', 14, 4)->default(0);
                $table->timestamps();

                $table->index(['tenant_id', 'product_id']);
                $table->index(['tenant_id', 'product_variant_id']);
            });
        }

        if (!Schema::hasTable('pm_team_members')) {
            Schema::create('pm_team_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('full_name', 255);
                $table->string('email', 255)->nullable();
                $table->string('phone', 60)->nullable();
                $table->string('role', 120)->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('pm_payroll_entries')) {
            Schema::create('pm_payroll_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('team_member_id')->nullable()->constrained('pm_team_members')->nullOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('pm_projects')->nullOnDelete();
                $table->string('payment_type', 30)->default('daily');
                $table->decimal('amount', 14, 4);
                $table->string('currency_code', 3)->default('USD');
                $table->date('paid_at');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'paid_at']);
                $table->index(['tenant_id', 'payment_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_payroll_entries');
        Schema::dropIfExists('pm_team_members');
        Schema::dropIfExists('pm_quotation_items');
        Schema::dropIfExists('pm_projects');
        Schema::dropIfExists('pm_quotations');
    }
};
