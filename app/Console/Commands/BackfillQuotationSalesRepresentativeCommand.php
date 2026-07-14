<?php

namespace App\Console\Commands;

use App\Models\ProjectQuotation;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Console\Command;

class BackfillQuotationSalesRepresentativeCommand extends Command
{
    protected $signature = 'sales:backfill-quotation-representative
        {--apply : Aplica los cambios en base de datos. Sin esta opcion solo muestra vista previa.}
        {--fallback-user-id= : Usuario vendedor/administrador a usar cuando la cotizacion no tenga creador valido.}';

    protected $description = 'Completa sales_rep_user_id en ventas provenientes de cotizaciones que quedaron sin "Realizada por"';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $fallbackUserId = (int) ($this->option('fallback-user-id') ?: 0);

        $fallbackUser = null;
        if ($fallbackUserId > 0) {
            $fallbackUser = User::query()
                ->select(['id', 'tenant_id'])
                ->find($fallbackUserId);

            if (!$fallbackUser) {
                $this->error('No existe el usuario indicado en --fallback-user-id=' . $fallbackUserId . '.');
                return self::FAILURE;
            }
        }

        $quotations = ProjectQuotation::query()
            ->select(['id', 'tenant_id', 'created_by', 'converted_sale_reference', 'conversion_target'])
            ->where('conversion_target', 'sale')
            ->whereNotNull('created_by')
            ->where('created_by', '>', 0)
            ->orderBy('id')
            ->get();

        $scanned = 0;
        $candidateOrders = 0;
        $updated = 0;
        $skippedMissingCreator = 0;
        $fallbackUsed = 0;

        foreach ($quotations as $quotation) {
            $scanned += 1;

            $creatorId = (int) ($quotation->created_by ?? 0);
            $creator = null;
            if ($creatorId > 0) {
                $creator = User::query()
                    ->select(['id', 'tenant_id'])
                    ->find($creatorId);
            }

            if (!$creator || (int) ($creator->tenant_id ?? 0) !== (int) ($quotation->tenant_id ?? 0)) {
                if ($fallbackUser && (int) ($fallbackUser->tenant_id ?? 0) === (int) ($quotation->tenant_id ?? 0)) {
                    $creatorId = (int) $fallbackUser->id;
                    $fallbackUsed += 1;
                } else {
                    $skippedMissingCreator += 1;
                    continue;
                }
            }

            $orderIds = $this->resolveCandidateSalesOrderIds($quotation);
            if (empty($orderIds)) {
                continue;
            }

            $ordersQuery = SalesOrder::query()
                ->where('tenant_id', (int) $quotation->tenant_id)
                ->whereIn('id', $orderIds)
                ->where(function ($query) {
                    $query->whereNull('sales_rep_user_id')
                        ->orWhere('sales_rep_user_id', 0);
                });

            $candidateOrders += (int) $ordersQuery->count();

            if ($apply) {
                $updated += (int) $ordersQuery->update([
                    'sales_rep_user_id' => $creatorId,
                    'updated_at' => now(),
                ]);
            }
        }

        $modeLabel = $apply ? 'APLICADO' : 'VISTA PREVIA';

        $this->newLine();
        $this->info('Backfill ' . $modeLabel . ' completado.');
        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Cotizaciones evaluadas', (string) $scanned],
                ['Ventas candidatas sin Realizada por', (string) $candidateOrders],
                ['Ventas actualizadas', (string) $updated],
                ['Cotizaciones corregidas con fallback', (string) $fallbackUsed],
                ['Cotizaciones omitidas por creador invalido', (string) $skippedMissingCreator],
            ]
        );

        if (!$apply) {
            $this->line('Ejecuta con --apply para guardar cambios.');
            if ($fallbackUserId > 0) {
                $this->line('Fallback activo con usuario #' . $fallbackUserId . '.');
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int>
     */
    private function resolveCandidateSalesOrderIds(ProjectQuotation $quotation): array
    {
        $ids = [];

        $reference = trim((string) ($quotation->converted_sale_reference ?? ''));
        if ($reference !== '' && preg_match('/VENTA\s*#\s*(\d+)/iu', $reference, $matches) === 1) {
            $saleId = (int) ($matches[1] ?? 0);
            if ($saleId > 0) {
                $ids[] = $saleId;
            }
        }

        // Fallback: ventas creadas con texto estandar de conversion desde cotizacion.
        $addressBasedIds = SalesOrder::query()
            ->where('tenant_id', (int) ($quotation->tenant_id ?? 0))
            ->where('address', 'like', '%' . (string) ('cotización #' . (int) $quotation->id) . '%')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return array_values(array_unique(array_merge($ids, $addressBasedIds)));
    }
}
