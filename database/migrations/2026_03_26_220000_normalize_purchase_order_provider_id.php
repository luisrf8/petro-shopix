<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('purchase_orders') || !Schema::hasTable('providers')) {
            return;
        }

        if (!Schema::hasColumn('purchase_orders', 'provider_name')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('provider_name')->nullable()->after('provider_id');
            });
        }

        $providerColumn = DB::selectOne("SHOW COLUMNS FROM purchase_orders LIKE 'provider_id'");
        $providerType = strtolower((string) ($providerColumn->Type ?? ''));

        if (str_contains($providerType, 'char') || str_contains($providerType, 'text')) {
            DB::statement("UPDATE purchase_orders SET provider_name = provider_id WHERE provider_name IS NULL OR provider_name = ''");

            if (!Schema::hasColumn('purchase_orders', 'provider_ref_id')) {
                Schema::table('purchase_orders', function (Blueprint $table) {
                    $table->unsignedBigInteger('provider_ref_id')->nullable()->after('provider_name');
                });
            }

            $orders = DB::table('purchase_orders')
                ->select('tenant_id', 'provider_name')
                ->whereNotNull('tenant_id')
                ->whereNotNull('provider_name')
                ->get()
                ->filter(fn ($row) => trim((string) $row->provider_name) !== '')
                ->unique(fn ($row) => $row->tenant_id . '|' . trim((string) $row->provider_name));

            foreach ($orders as $row) {
                $providerName = trim((string) $row->provider_name);
                $provider = DB::table('providers')->where('tenant_id', $row->tenant_id)->where('name', $providerName)->first();

                if (!$provider) {
                    $providerId = DB::table('providers')->insertGetId([
                        'tenant_id' => $row->tenant_id,
                        'name' => $providerName,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $providerId = (int) $provider->id;
                }

                DB::table('purchase_orders')
                    ->where('tenant_id', $row->tenant_id)
                    ->where('provider_name', $providerName)
                    ->update(['provider_ref_id' => $providerId]);
            }

            DB::statement('ALTER TABLE purchase_orders DROP COLUMN provider_id');
            DB::statement('ALTER TABLE purchase_orders CHANGE provider_ref_id provider_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_provider_id_foreign FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE SET NULL');
        } else {
            DB::statement("UPDATE purchase_orders po LEFT JOIN providers p ON p.id = po.provider_id SET po.provider_name = COALESCE(po.provider_name, p.name)");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('purchase_orders')) {
            return;
        }

        $providerColumn = DB::selectOne("SHOW COLUMNS FROM purchase_orders LIKE 'provider_id'");
        $providerType = strtolower((string) ($providerColumn->Type ?? ''));

        if (!str_contains($providerType, 'char') && !str_contains($providerType, 'text')) {
            DB::statement('ALTER TABLE purchase_orders DROP FOREIGN KEY purchase_orders_provider_id_foreign');
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('provider_legacy')->nullable()->after('provider_name');
            });
            DB::statement('UPDATE purchase_orders SET provider_legacy = provider_name');
            DB::statement('ALTER TABLE purchase_orders DROP COLUMN provider_id');
            DB::statement('ALTER TABLE purchase_orders CHANGE provider_legacy provider_id VARCHAR(255) NULL');
        }
    }
};