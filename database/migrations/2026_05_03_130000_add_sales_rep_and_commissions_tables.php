<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'commission_percentage')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('commission_percentage', 5, 2)->default(0)->after('is_active');
            });
        }

        if (Schema::hasTable('sales_orders') && !Schema::hasColumn('sales_orders', 'sales_rep_user_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('sales_rep_user_id')->nullable()->after('user_id');
                $table->foreign('sales_rep_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('seller_commissions')) {
            Schema::create('seller_commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
                $table->decimal('commission_base_amount', 14, 4)->default(0);
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->decimal('commission_amount', 14, 4)->default(0);
                $table->string('currency_code', 3)->default('USD');
                $table->string('status', 20)->default('pending');
                $table->timestamp('calculated_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'sales_order_id']);
                $table->index(['tenant_id', 'seller_user_id']);
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_commissions');

        if (Schema::hasTable('sales_orders') && Schema::hasColumn('sales_orders', 'sales_rep_user_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropForeign(['sales_rep_user_id']);
                $table->dropColumn('sales_rep_user_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'commission_percentage')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('commission_percentage');
            });
        }
    }
};
