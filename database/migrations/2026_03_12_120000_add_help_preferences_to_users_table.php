<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHelpPreferencesToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'help_disable_global')) {
                $table->boolean('help_disable_global')->default(false);
            }

            if (!Schema::hasColumn('users', 'help_disabled_routes')) {
                $table->json('help_disabled_routes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'help_disabled_routes')) {
                $table->dropColumn('help_disabled_routes');
            }

            if (Schema::hasColumn('users', 'help_disable_global')) {
                $table->dropColumn('help_disable_global');
            }
        });
    }
}
