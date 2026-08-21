<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date_format:Y-m-d\\TH:i'],
            'to' => ['nullable', 'date_format:Y-m-d\\TH:i'],
        ]);
        $search = $filters['search'] ?? '';

        return Inertia::render('ActivityLogs/Index', [
            'logs' => ActivityLog::query()
                ->with('user:id,name,role')
                ->whereHas('user', fn ($query) => $query->where('role', 'restricted'))
                ->when($search, fn ($query) => $query->where(fn ($inner) => $inner->where('action', 'like', "%{$search}%")->orWhere('module', 'like', "%{$search}%")->orWhere('subject_label', 'like', "%{$search}%")))
                ->when($filters['from'] ?? null, fn ($query, $value) => $query->where('created_at', '>=', $this->utcDateTime($value)))
                ->when($filters['to'] ?? null, fn ($query, $value) => $query->where('created_at', '<=', $this->utcDateTime($value)))
                ->latest()
                ->paginate(100)
                ->withQueryString()
                ->through(fn (ActivityLog $log) => $this->serialize($log)),
            'filters' => ['search' => $search, 'from' => $filters['from'] ?? '', 'to' => $filters['to'] ?? ''],
        ]);
    }

    private function utcDateTime(string $value): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\\TH:i', $value, 'Africa/Casablanca')->utc();
    }

    private function serialize(ActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'created_at' => $log->created_at->timezone('Africa/Casablanca')->format('d/m/Y H:i'),
            'user' => ['name' => $log->user?->name ?? ''],
            'action' => ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression'][$log->action] ?? $log->action,
            'module' => $log->module,
            'subject_label' => $log->subject_label,
            'url' => $this->documentUrl($log),
            'before' => $this->details($log->before),
            'after' => $this->details($log->after),
        ];
    }

    private function documentUrl(ActivityLog $log): ?string
    {
        if ($log->action === 'deleted') {
            return null;
        }

        $attributes = array_merge($log->before ?? [], $log->after ?? []);
        $model = $this->subjectModel($log);
        $attribute = fn (string $key) => $model?->getAttribute($key) ?? $attributes[$key] ?? null;
        $clientId = $attribute('client_id');
        $fournisseurId = $attribute('fournisseur_id');
        $releveId = $attribute('fournisseur_releve_compte_id') ?? ($log->subject_type === 'FournisseurReleveCompte' ? $log->subject_id : null);
        $operationId = $attribute('operation_id') ?? ($log->subject_type === 'Operation' ? $log->subject_id : null);

        return match ($log->subject_type) {
            'Cheque' => route('cheques.index', ['search' => $log->subject_label]),
            'ChequeImpaye' => route('cheques.impayes.index', ['search' => $log->subject_label]),
            'Client' => route('clients.show', $log->subject_id),
            'ClientEntry', 'ClientPayment', 'ChequeClient' => $clientId ? route('clients.show', $clientId) : route('clients.index'),
            'Fournisseur' => route('fournisseurs.show', $log->subject_id),
            'FournisseurReleveCompte', 'FournisseurFacture', 'FournisseurCheque' => $fournisseurId && $releveId ? route('fournisseurs.releves.show', [$fournisseurId, $releveId]) : ($fournisseurId ? route('fournisseurs.show', $fournisseurId) : route('fournisseurs.index')),
            'Depot' => route('depots.show', $log->subject_id),
            'Article' => route('depots.index'),
            'Operation', 'OperationLine' => $operationId ? route('operations.show', $operationId) : route('operations.index'),
            default => null,
        };
    }

    private function subjectModel(ActivityLog $log): ?Model
    {
        $class = 'App\\Models\\'.$log->subject_type;

        return class_exists($class) && is_a($class, Model::class, true) ? $class::find($log->subject_id) : null;
    }

    private function details(?array $attributes): array
    {
        return collect($attributes ?? [])
            ->reject(fn ($value, string $key) => in_array($key, ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'pin', 'remember_token'], true) || str_ends_with($key, '_id'))
            ->map(fn ($value, string $key) => ['label' => $this->label($key), 'value' => $this->value($key, $value)])
            ->values()
            ->all();
    }

    private function label(string $key): string
    {
        return [
            'name' => 'Nom', 'nom' => 'Nom', 'numero_cheque' => 'N° chèque', 'numero_facture' => 'N° facture', 'client_nom' => 'Client', 'fournisseur_nom' => 'Fournisseur', 'fournisseur_sortie_nom' => 'Fournisseur',
            'type' => 'Type', 'statut' => 'Statut', 'mode' => 'Mode de paiement', 'mode_paiement' => 'Mode de paiement', 'montant' => 'Montant', 'amount' => 'Montant', 'salary' => 'Salaire',
            'date_emission' => 'Date d’émission', 'date_echeance' => 'Date d’échéance', 'date_paiement' => 'Date de paiement', 'payment_date' => 'Date de paiement', 'date_remise' => 'Date de remise',
            'facture_recue' => 'Facture reçue', 'facture_donnee' => 'Facture donnée', 'est_sorti' => 'Chèque sorti', 'note' => 'Note', 'motif' => 'Motif', 'description' => 'Description',
            'permissions' => 'Autorisations', 'role' => 'Profil', 'month' => 'Mois', 'absence_date' => 'Date d’absence', 'status' => 'Statut',
        ][$key] ?? str($key)->replace('_', ' ')->ucfirst()->toString();
    }

    private function value(string $key, mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value) || (in_array($key, ['facture_recue', 'facture_donnee', 'est_sorti'], true) && in_array($value, [0, 1, '0', '1'], true))) {
            return (bool) $value ? 'Oui' : 'Non';
        }

        if ($key === 'permissions') {
            $permissions = is_string($value) ? json_decode($value, true) : $value;
            $modules = collect($permissions['modules'] ?? [])->map(fn ($item) => $this->moduleName($item))->join(', ');
            $deletes = collect($permissions['delete'] ?? [])->map(fn ($item) => $this->moduleName($item))->join(', ');
            return trim('Accès : '.($modules ?: 'Aucun').($deletes ? ' · Suppression : '.$deletes : ''));
        }

        $values = ['cheque' => 'Chèque', 'effet' => 'Effet', 'espece' => 'Espèce', 'virement' => 'Virement', 'en_cours' => 'En cours', 'en_caisse' => 'En caisse', 'impaye' => 'Impayé', 'paye' => 'Payé', 'salary' => 'Salaire', 'advance' => 'Avance', 'bonus' => 'Prime', 'restricted' => 'Accès limité', 'admin' => 'Administrateur', 'pending' => 'En attente', 'paid' => 'Payé', 'absent' => 'Absent', 'worked' => 'Travaillé'];
        $string = $values[$value] ?? (string) $value;

        if (in_array($key, ['montant', 'amount', 'salary'], true) && is_numeric($value)) {
            return number_format((float) $value, 2, ',', ' ').' DH';
        }
        if (str_starts_with($key, 'date_') || in_array($key, ['payment_date', 'absence_date'], true)) {
            return Carbon::parse($value)->timezone('Africa/Casablanca')->format('d/m/Y');
        }
        if ($key === 'month' && preg_match('/^\\d{4}-\\d{2}$/', (string) $value)) {
            return Carbon::createFromFormat('Y-m', $value)->locale('fr')->translatedFormat('F Y');
        }

        return $string === '' ? '—' : $string;
    }

    private function moduleName(string $module): string
    {
        return ['dashboard' => 'Dashboard', 'depots' => 'Dépôt', 'fournisseurs' => 'Fournisseurs', 'clients' => 'Clients', 'employees' => 'RH / Employés', 'cheques' => 'Chèques'][$module] ?? $module;
    }
}
