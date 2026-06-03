<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!$this->indexExists('tenant_plan_payments', 'idx_tpp_tenant_status_paid_id')) {
            DB::statement('CREATE INDEX idx_tpp_tenant_status_paid_id ON tenant_plan_payments (tenant_id, status, paid_at, id)');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('tenant_plan_payments', 'idx_tpp_tenant_status_paid_id')) {
            DB::statement('DROP INDEX idx_tpp_tenant_status_paid_id ON tenant_plan_payments');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(1) AS aggregate
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
