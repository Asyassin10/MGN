<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    private array $before = [];

    public function created(Model $model): void
    {
        $this->write($model, 'created', null, $model->getAttributes());
    }

    public function updating(Model $model): void
    {
        $this->before[spl_object_id($model)] = array_intersect_key($model->getRawOriginal(), $model->getDirty());
    }

    public function updated(Model $model): void
    {
        $dirty = $model->getChanges();
        $this->write($model, 'updated', $this->before[spl_object_id($model)] ?? null, $dirty);
        unset($this->before[spl_object_id($model)]);
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted', $model->getOriginal(), null);
    }

    private function write(Model $model, string $action, ?array $before, ?array $after): void
    {
        if (! auth()->check()) {
            return;
        }
        ActivityLog::create(['user_id' => auth()->id(), 'action' => $action, 'module' => $this->module($model), 'subject_type' => class_basename($model), 'subject_id' => $model->getKey(), 'subject_label' => $this->label($model), 'before' => $this->clean($before), 'after' => $this->clean($after)]);
    }

    private function module(Model $model): string
    {
        return match (class_basename($model)) {
            'Fournisseur', 'FournisseurFacture', 'FournisseurCheque', 'FournisseurReleveCompte' => 'Fournisseurs', 'Client', 'ClientEntry', 'ClientPayment', 'ChequeClient' => 'Clients', 'Depot', 'Article', 'Operation', 'OperationLine' => 'Dépôt', 'Employee', 'EmployeeWorkDay', 'EmployeeAbsence', 'EmployeeSalaryPayment' => 'RH', 'User' => 'Utilisateurs', default => 'Paramètres'
        };
    }

    private function label(Model $model): string
    {
        return (string) ($model->getAttribute('nom') ?? $model->getAttribute('name') ?? $model->getAttribute('numero_cheque') ?? $model->getAttribute('numero_facture') ?? '#'.$model->getKey());
    }

    private function clean(?array $attributes): ?array
    {
        if (! $attributes) {
            return $attributes;
        }
        unset($attributes['password'], $attributes['pin'], $attributes['remember_token']);

        return $attributes;
    }
}
