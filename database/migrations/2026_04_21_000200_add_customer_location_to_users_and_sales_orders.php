<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('tenant_id')->constrained('countries')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'state_id')) {
                $table->foreignId('state_id')->nullable()->after('country_id')->constrained('states')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'city_id')) {
                $table->foreignId('city_id')->nullable()->after('state_id')->constrained('cities')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address', 500)->nullable()->after('city_id');
            }

            if (!Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('address');
            }

            if (!Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'delivery_latitude')) {
                $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_distance_km');
            }

            if (!Schema::hasColumn('sales_orders', 'delivery_longitude')) {
                $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('sales_orders', 'delivery_longitude') ? 'delivery_longitude' : null,
                Schema::hasColumn('sales_orders', 'delivery_latitude') ? 'delivery_latitude' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['country_id', 'state_id', 'city_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    try {
                        $table->dropConstrainedForeignId($column);
                    } catch (\Throwable $exception) {
                        $table->dropColumn($column);
                    }
                }
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('users', 'address') ? 'address' : null,
                Schema::hasColumn('users', 'latitude') ? 'latitude' : null,
                Schema::hasColumn('users', 'longitude') ? 'longitude' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
