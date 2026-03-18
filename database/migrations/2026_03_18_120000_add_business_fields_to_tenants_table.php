<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'business_type')) {
                $table->string('business_type')->nullable()->after('description');
            }

            if (!Schema::hasColumn('tenants', 'economic_activity')) {
                $table->string('economic_activity')->nullable()->after('business_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('tenants', 'business_type')) {
                $dropColumns[] = 'business_type';
            }

            if (Schema::hasColumn('tenants', 'economic_activity')) {
                $dropColumns[] = 'economic_activity';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
