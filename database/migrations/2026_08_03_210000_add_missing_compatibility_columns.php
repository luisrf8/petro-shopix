<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('password');
            });

            DB::table('users')->whereNull('is_active')->update(['is_active' => 1]);
        }

        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'tenant_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });

            $defaultTenantId = (int) (DB::table('tenants')->orderBy('id')->value('id') ?? 0);
            if ($defaultTenantId > 0) {
                DB::table('categories')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            }
        }

        if (Schema::hasTable('sales_orders') && !Schema::hasColumn('sales_orders', 'status')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->unsignedTinyInteger('status')->default(1)->after('tenant_id');
                $table->index('status');
            });

            DB::table('sales_orders')->whereNull('status')->update(['status' => 1]);
        }

        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'status')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedTinyInteger('status')->default(1)->after('amount');
                $table->index('status');
            });

            DB::table('payments')->whereNull('status')->update(['status' => 1]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'status')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            });
        }

        if (Schema::hasTable('sales_orders') && Schema::hasColumn('sales_orders', 'status')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            });
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'tenant_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
