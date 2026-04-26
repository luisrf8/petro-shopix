<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
        });

        $products = DB::table('products')
            ->select('id', 'tenant_id', 'name')
            ->orderBy('tenant_id')
            ->orderBy('id')
            ->get();

        $usedSlugs = [];

        foreach ($products as $product) {
            $tenantId = (int) ($product->tenant_id ?? 0);
            $baseSlug = Str::slug((string) ($product->name ?? ''));
            if ($baseSlug === '') {
                $baseSlug = 'producto';
            }

            $slug = $baseSlug;
            $suffix = 2;
            while (isset($usedSlugs[$tenantId][$slug])) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $usedSlugs[$tenantId][$slug] = true;

            DB::table('products')
                ->where('id', $product->id)
                ->update(['slug' => $slug]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['tenant_id', 'slug'], 'products_tenant_id_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_tenant_id_slug_unique');

            if (Schema::hasColumn('products', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
