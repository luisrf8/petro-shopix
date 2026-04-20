<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('audit_log')) {
            Schema::create('audit_log', function (Blueprint $table) {
                $table->id();
                $table->string('table_name', 100)->default('system');
                $table->string('action', 100)->default('UNKNOWN');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->string('event_type', 50)->nullable();
                $table->string('record_id', 100)->nullable();
                $table->text('description')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->timestamps();

                $table->index(['table_name', 'action']);
                $table->index(['user_id', 'occurred_at']);
                $table->index(['tenant_id', 'occurred_at']);
            });
        } else {
            Schema::table('audit_log', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_log', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->after('user_id')->constrained('tenants')->nullOnDelete();
                }

                if (!Schema::hasColumn('audit_log', 'event_type')) {
                    $table->string('event_type', 50)->nullable()->after('tenant_id');
                }

                if (!Schema::hasColumn('audit_log', 'record_id')) {
                    $table->string('record_id', 100)->nullable()->after('event_type');
                }

                if (!Schema::hasColumn('audit_log', 'old_values')) {
                    $table->json('old_values')->nullable()->after('description');
                }

                if (!Schema::hasColumn('audit_log', 'new_values')) {
                    $table->json('new_values')->nullable()->after('old_values');
                }

                if (!Schema::hasColumn('audit_log', 'ip_address')) {
                    $table->string('ip_address', 45)->nullable()->after('new_values');
                }

                if (!Schema::hasColumn('audit_log', 'occurred_at')) {
                    $table->timestamp('occurred_at')->nullable()->after('ip_address');
                }

                if (!Schema::hasColumn('audit_log', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
        }

        if (!Schema::hasTable('fiscal_correlatives')) {
            Schema::create('fiscal_correlatives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('document_key', 50);
                $table->string('prefix', 20);
                $table->unsignedBigInteger('current_number')->default(0);
                $table->timestamps();

                $table->unique(['tenant_id', 'document_key']);
            });
        }

        if (Schema::hasTable('electronic_documents')) {
            Schema::table('electronic_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('electronic_documents', 'internal_number')) {
                    $table->string('internal_number', 60)->nullable()->after('numero_documento');
                }
            });
        }

        if (Schema::hasTable('sales_retentions')) {
            Schema::table('sales_retentions', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_retentions', 'internal_number')) {
                    $table->string('internal_number', 60)->nullable()->after('retention_date');
                }
            });
        }

        $this->backfillFiscalInternalNumbers();

        if (Schema::hasTable('electronic_documents')) {
            Schema::table('electronic_documents', function (Blueprint $table) {
                $table->unique(['tenant_id', 'internal_number'], 'electronic_documents_tenant_internal_number_unique');
            });
        }

        if (Schema::hasTable('sales_adjustment_notes')) {
            Schema::table('sales_adjustment_notes', function (Blueprint $table) {
                $table->unique(['tenant_id', 'internal_number'], 'sales_adjustment_notes_tenant_internal_number_unique');
            });
        }

        if (Schema::hasTable('sales_retentions')) {
            Schema::table('sales_retentions', function (Blueprint $table) {
                $table->unique(['tenant_id', 'internal_number'], 'sales_retentions_tenant_internal_number_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_retentions')) {
            Schema::table('sales_retentions', function (Blueprint $table) {
                if (Schema::hasColumn('sales_retentions', 'internal_number')) {
                    $table->dropUnique('sales_retentions_tenant_internal_number_unique');
                    $table->dropColumn('internal_number');
                }
            });
        }

        if (Schema::hasTable('sales_adjustment_notes')) {
            Schema::table('sales_adjustment_notes', function (Blueprint $table) {
                if (Schema::hasColumn('sales_adjustment_notes', 'internal_number')) {
                    $table->dropUnique('sales_adjustment_notes_tenant_internal_number_unique');
                }
            });
        }

        if (Schema::hasTable('electronic_documents')) {
            Schema::table('electronic_documents', function (Blueprint $table) {
                if (Schema::hasColumn('electronic_documents', 'internal_number')) {
                    $table->dropUnique('electronic_documents_tenant_internal_number_unique');
                    $table->dropColumn('internal_number');
                }
            });
        }

        Schema::dropIfExists('fiscal_correlatives');
    }

    private function backfillFiscalInternalNumbers(): void
    {
        $counters = [];

        $this->hydrateCountersFromExisting('electronic_documents', 'invoice', 'FAC', $counters);
        $this->hydrateCountersFromExisting('sales_retentions', 'retention', 'RET', $counters);
        $this->hydrateCountersFromExisting('sales_adjustment_notes', 'credit_note', 'NC', $counters, 'credit');
        $this->hydrateCountersFromExisting('sales_adjustment_notes', 'debit_note', 'ND', $counters, 'debit');

        $this->backfillTable('electronic_documents', 'invoice', 'FAC', $counters);
        $this->backfillTable('sales_retentions', 'retention', 'RET', $counters);
        $this->backfillTable('sales_adjustment_notes', 'credit_note', 'NC', $counters, 'credit');
        $this->backfillTable('sales_adjustment_notes', 'debit_note', 'ND', $counters, 'debit');

        foreach ($counters as $tenantId => $documents) {
            foreach ($documents as $documentKey => $state) {
                DB::table('fiscal_correlatives')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'document_key' => $documentKey,
                    ],
                    [
                        'prefix' => $state['prefix'],
                        'current_number' => $state['current_number'],
                        'updated_at' => now(),
                        'created_at' => $state['created_at'] ?? now(),
                    ]
                );
            }
        }
    }

    private function hydrateCountersFromExisting(string $table, string $documentKey, string $prefix, array &$counters, ?string $noteType = null): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'internal_number')) {
            return;
        }

        $query = DB::table($table)
            ->select('tenant_id', 'internal_number')
            ->whereNotNull('internal_number')
            ->where('internal_number', '!=', '');

        if ($noteType !== null && Schema::hasColumn($table, 'note_type')) {
            $query->where('note_type', $noteType);
        }

        foreach ($query->orderBy('tenant_id')->cursor() as $row) {
            $tenantId = (int) ($row->tenant_id ?? 0);
            if ($tenantId <= 0) {
                continue;
            }

            $numeric = $this->extractNumericSuffix((string) $row->internal_number);
            if ($numeric <= 0) {
                continue;
            }

            $current = $counters[$tenantId][$documentKey]['current_number'] ?? 0;
            if ($numeric > $current) {
                $counters[$tenantId][$documentKey] = [
                    'prefix' => $prefix,
                    'current_number' => $numeric,
                    'created_at' => now(),
                ];
            }
        }
    }

    private function backfillTable(string $table, string $documentKey, string $prefix, array &$counters, ?string $noteType = null): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'internal_number')) {
            return;
        }

        $query = DB::table($table)
            ->select('id', 'tenant_id', 'internal_number')
            ->where(function ($subQuery) {
                $subQuery->whereNull('internal_number')->orWhere('internal_number', '');
            });

        if ($noteType !== null && Schema::hasColumn($table, 'note_type')) {
            $query->where('note_type', $noteType);
        }

        foreach ($query->orderBy('tenant_id')->orderBy('id')->cursor() as $row) {
            $tenantId = (int) ($row->tenant_id ?? 0);
            if ($tenantId <= 0) {
                continue;
            }

            $current = (int) ($counters[$tenantId][$documentKey]['current_number'] ?? 0) + 1;
            $counters[$tenantId][$documentKey] = [
                'prefix' => $prefix,
                'current_number' => $current,
                'created_at' => $counters[$tenantId][$documentKey]['created_at'] ?? now(),
            ];

            DB::table($table)
                ->where('id', $row->id)
                ->update([
                    'internal_number' => $prefix . '-' . str_pad((string) $current, 8, '0', STR_PAD_LEFT),
                ]);
        }
    }

    private function extractNumericSuffix(string $value): int
    {
        if (preg_match('/(\d+)$/', trim($value), $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }
};