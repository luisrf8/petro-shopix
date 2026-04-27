<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('sales_order_id')->nullable()->after('customer_id')->constrained('sales_orders')->nullOnDelete();
            $table->timestamp('called_at')->nullable()->after('source');
            $table->foreignId('called_by_user_id')->nullable()->after('called_at')->constrained('users')->nullOnDelete();
            $table->timestamp('attendance_confirmed_at')->nullable()->after('called_by_user_id');
            $table->foreignId('attendance_confirmed_by_user_id')->nullable()->after('attendance_confirmed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('attendance_confirmed_by_user_id');
            $table->foreignId('cancelled_by_user_id')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rescheduled_at')->nullable()->after('cancelled_by_user_id');
            $table->foreignId('rescheduled_by_user_id')->nullable()->after('rescheduled_at')->constrained('users')->nullOnDelete();
            $table->foreignId('rescheduled_from_appointment_id')->nullable()->after('rescheduled_by_user_id')->constrained('appointments')->nullOnDelete();
            $table->string('workflow_tag', 50)->nullable()->after('rescheduled_from_appointment_id');
            $table->text('workflow_note')->nullable()->after('workflow_tag');
            $table->index(['tenant_id', 'customer_id', 'starts_at'], 'appointment_customer_agenda_idx');
            $table->index(['tenant_id', 'sales_order_id'], 'appointment_sales_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointment_customer_agenda_idx');
            $table->dropIndex('appointment_sales_order_idx');
            $table->dropConstrainedForeignId('sales_order_id');
            $table->dropConstrainedForeignId('called_by_user_id');
            $table->dropConstrainedForeignId('attendance_confirmed_by_user_id');
            $table->dropConstrainedForeignId('cancelled_by_user_id');
            $table->dropConstrainedForeignId('rescheduled_by_user_id');
            $table->dropConstrainedForeignId('rescheduled_from_appointment_id');
            $table->dropColumn([
                'called_at',
                'attendance_confirmed_at',
                'cancelled_at',
                'rescheduled_at',
                'workflow_tag',
                'workflow_note',
            ]);
        });
    }
};
