<?php

namespace App\Services;

use App\Models\FiscalCorrelative;
use Illuminate\Support\Facades\DB;

class FiscalCorrelativeService
{
    public function next(int $tenantId, string $documentKey, string $prefix): string
    {
        return DB::transaction(function () use ($tenantId, $documentKey, $prefix) {
            $sequence = FiscalCorrelative::query()
                ->where('tenant_id', $tenantId)
                ->where('document_key', $documentKey)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                $sequence = FiscalCorrelative::create([
                    'tenant_id' => $tenantId,
                    'document_key' => $documentKey,
                    'prefix' => $prefix,
                    'current_number' => 0,
                ]);
            }

            $sequence->prefix = $prefix;
            $sequence->current_number = (int) $sequence->current_number + 1;
            $sequence->save();

            return strtoupper(trim($sequence->prefix)) . '-' . str_pad((string) $sequence->current_number, 8, '0', STR_PAD_LEFT);
        });
    }
}