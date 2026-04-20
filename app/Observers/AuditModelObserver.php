<?php

namespace App\Observers;

use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditModelObserver
{
    private static array $updatingSnapshots = [];
    private static array $deletingSnapshots = [];

    public function updating(Model $model): void
    {
        if (!$this->shouldAudit($model)) {
            return;
        }

        self::$updatingSnapshots[spl_object_id($model)] = [
            'old' => $this->sanitize($model->getOriginal()),
        ];
    }

    public function updated(Model $model): void
    {
        if (!$this->shouldAudit($model)) {
            return;
        }

        $key = spl_object_id($model);
        $oldValues = self::$updatingSnapshots[$key]['old'] ?? $this->sanitize($model->getOriginal());
        unset(self::$updatingSnapshots[$key]);

        $newValues = $this->sanitize($model->fresh()?->getAttributes() ?? $model->getAttributes());
        AuditLogger::logModelChange($model, 'UPDATE', 'Actualización de registro', $oldValues, $newValues);
    }

    public function deleting(Model $model): void
    {
        if (!$this->shouldAudit($model)) {
            return;
        }

        self::$deletingSnapshots[spl_object_id($model)] = [
            'old' => $this->sanitize($model->getOriginal()),
        ];
    }

    public function deleted(Model $model): void
    {
        if (!$this->shouldAudit($model)) {
            return;
        }

        $key = spl_object_id($model);
        $oldValues = self::$deletingSnapshots[$key]['old'] ?? $this->sanitize($model->getOriginal());
        unset(self::$deletingSnapshots[$key]);

        AuditLogger::logModelChange($model, 'DELETE', 'Eliminación de registro', $oldValues, null);
    }

    private function shouldAudit(Model $model): bool
    {
        return !in_array($model->getTable(), ['audit_log', 'fiscal_correlatives', 'jobs', 'failed_jobs'], true);
    }

    private function sanitize(array $attributes): array
    {
        $sanitized = [];

        foreach ($attributes as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $sanitized[$key] = $value;
            }
        }

        unset($sanitized['password'], $sanitized['remember_token']);

        return $sanitized;
    }
}