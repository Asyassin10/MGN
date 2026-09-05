<?php

namespace App\Services;

use App\Models\Fournisseur;
use App\Models\FournisseurCheque;
use App\Models\FournisseurReleveCompte;
use App\Support\DownloadFilename;
use App\Support\ExcelExport;
use App\Support\FinancePdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FournisseurService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($filters)->latest()->paginate(100)->withQueryString()->through(fn (Fournisseur $fournisseur) => $this->serialize($fournisseur));
    }

    public function relevesList(array $filters): LengthAwarePaginator
    {
        return $this->relevesQuery($filters)->latest('date_releve')->paginate(100)->withQueryString()->through(fn (FournisseurReleveCompte $releve) => $this->serializeGlobalReleve($releve));
    }

    public function topBalances(): array
    {
        return Fournisseur::query()->withSum('factures', 'montant')->withSum('cheques', 'montant')->get()
            ->map(fn (Fournisseur $fournisseur) => $this->serialize($fournisseur))->sortByDesc('balance')->take(8)->values()->all();
    }

    public function show(Fournisseur $fournisseur, array $filters): array
    {
        $fournisseur->loadSum('factures', 'montant')->loadSum('cheques', 'montant')->loadCount(['factures', 'cheques']);

        return [
            'fournisseur' => $this->serialize($fournisseur),
            'releves' => $fournisseur->releveComptes()->withSum('factures', 'montant')->withSum('cheques', 'montant')->withCount(['factures', 'cheques'])->latest('date_releve')->paginate(100)->withQueryString()->through(fn (FournisseurReleveCompte $releve) => $this->serializeReleve($releve)),
        ];
    }

    public function releve(Fournisseur $fournisseur, FournisseurReleveCompte $releve, array $filters): array
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id, 404);
        $fournisseur->loadSum('factures', 'montant')->loadSum('cheques', 'montant')->loadCount(['factures', 'cheques']);
        $releve->loadSum('factures', 'montant')->loadSum('cheques', 'montant')->loadCount(['factures', 'cheques']);

        return [
            'fournisseur' => $this->serialize($fournisseur),
            'releve' => $this->serializeReleve($releve),
            'factures' => $this->facturesQuery($releve, $filters)->latest('date_facture')->paginate(100, ['*'], 'factures_page')->withQueryString()->through(fn ($facture) => ['id' => $facture->id, 'numero_facture' => $facture->numero_facture, 'date_facture' => $facture->date_facture?->format('Y-m-d'), 'montant' => (float) $facture->montant, 'note' => $facture->note]),
            'payments' => $this->chequesQuery($releve, $filters)->latest('date_echeance')->latest('id')->paginate(100, ['*'], 'payments_page')->withQueryString()->through(fn (FournisseurCheque $cheque) => $this->serializeCheque($cheque)),
        ];
    }

    public function export(array $filters): StreamedResponse
    {
        $rows = $this->baseQuery($filters)->when($filters['selected_ids'] ?? [], fn (Builder $query, array $ids) => $query->whereKey($ids))->latest()->get()->map(fn (Fournisseur $fournisseur) => [$fournisseur->nom, $fournisseur->ville, $fournisseur->telephone, $fournisseur->factures_sum_montant ?? 0, $fournisseur->cheques_sum_montant ?? 0, $fournisseur->balance]);

        return ExcelExport::download('fournisseurs-export', ['Nom', 'Ville', 'Telephone', 'Total factures', 'Total cheques', 'Solde'], $rows);
    }

    public function exportReleves(Fournisseur $fournisseur, array $filters = []): StreamedResponse
    {
        $rows = $fournisseur->releveComptes()->when($filters['selected_ids'] ?? [], fn (Builder $query, array $ids) => $query->whereKey($ids))->withSum('factures', 'montant')->withSum('cheques', 'montant')->latest('date_releve')->get()->map(fn (FournisseurReleveCompte $releve) => [$releve->code_client, $releve->date_releve?->format('Y-m-d'), $releve->factures_sum_montant ?? 0, $releve->cheques_sum_montant ?? 0, $this->serializeReleve($releve)['balance']]);

        return ExcelExport::download('fournisseur-'.$fournisseur->id.'-releves-export', ['Code client', 'Date releve', 'Total factures', 'Total cheques', 'Solde'], $rows);
    }

    public function exportAllReleves(array $filters): StreamedResponse
    {
        $rows = $this->relevesQuery($filters)->when($filters['selected_ids'] ?? [], fn (Builder $query, array $ids) => $query->whereKey($ids))->latest('date_releve')->get()->map(fn (FournisseurReleveCompte $releve) => [$releve->fournisseur->nom, $releve->code_client, $releve->date_releve?->format('Y-m-d'), $releve->factures_sum_montant ?? 0, $releve->cheques_sum_montant ?? 0, $this->serializeReleve($releve)['balance']]);

        return ExcelExport::download('releves-compte-fournisseurs-export', ['Fournisseur', 'Code client', 'Date releve', 'Total factures', 'Total cheques', 'Solde'], $rows);
    }

    public function exportReleveFactures(FournisseurReleveCompte $releve, array $filters): StreamedResponse
    {
        return ExcelExport::download('releve-'.$releve->id.'-factures-export', ['Date facture', 'N facture', 'Montant', 'Note'], $this->facturesQuery($releve, $filters)->when($filters['selected_ids'] ?? [], fn (Builder $query, array $ids) => $query->whereKey($ids))->latest('date_facture')->get()->map(fn ($facture) => [$facture->date_facture?->format('Y-m-d'), $facture->numero_facture, $facture->montant, $facture->note]));
    }

    public function exportRelevePayments(FournisseurReleveCompte $releve, array $filters): StreamedResponse
    {
        return ExcelExport::download('releve-'.$releve->id.'-cheques-export', ['Numero', 'Type', 'Banque', 'Tireur / signataire', 'Montant', 'Emission', 'Echeance', 'Statut', 'Facture recue', 'Facture donnee'], $this->chequesQuery($releve, $filters)->when($filters['selected_ids'] ?? [], fn (Builder $query, array $ids) => $query->whereKey($ids))->latest('date_echeance')->latest('id')->get()->map(fn (FournisseurCheque $cheque) => [$cheque->numero_cheque, $cheque->type, $cheque->banque, $cheque->tireur_signataire, $cheque->montant, $cheque->date_emission?->format('Y-m-d'), $cheque->date_echeance?->format('Y-m-d'), $cheque->statut, $cheque->facture_recue ? 'Oui' : 'Non', $cheque->facture_donnee ? 'Oui' : 'Non']));
    }

    public function pdfReleve(Fournisseur $fournisseur, FournisseurReleveCompte $releve): Response
    {
        abort_if($releve->fournisseur_id !== $fournisseur->id, 404);
        $releve->load(['factures', 'cheques'])->loadSum('factures', 'montant')->loadSum('cheques', 'montant');
        $rows = $releve->factures->map(fn ($facture) => ['date' => $facture->date_facture?->format('d/m/Y'), 'designation' => $facture->numero_facture, 'montant' => number_format((float) $facture->montant, 2, ',', ' ').' MAD'])->all();
        foreach ($releve->cheques as $cheque) {
            $rows[] = ['date' => $cheque->date_emission?->format('d/m/Y'), 'designation' => ucfirst($cheque->type).' '.$cheque->numero_cheque, 'montant' => '-'.number_format((float) $cheque->montant, 2, ',', ' ').' MAD'];
        }

        return FinancePdf::preview(['title' => 'Releve compte '.$releve->code_client, 'subtitle' => 'Releve compte fournisseur', 'brand' => 'Droguerie Palmeraie', 'meta' => ['Fournisseur' => $fournisseur->nom, 'Code client' => $releve->code_client, 'Date releve' => $releve->date_releve?->format('d/m/Y')], 'columns' => [['key' => 'date', 'label' => 'Date'], ['key' => 'designation', 'label' => 'Designation'], ['key' => 'montant', 'label' => 'Montant', 'align' => 'right']], 'rows' => $rows, 'summary' => ['Total releve compte' => number_format((float) ($releve->factures_sum_montant ?? 0), 2, ',', ' ').' MAD', 'Total cheques' => number_format((float) ($releve->cheques_sum_montant ?? 0), 2, ',', ' ').' MAD', 'Reste' => number_format((float) (($releve->factures_sum_montant ?? 0) - ($releve->cheques_sum_montant ?? 0)), 2, ',', ' ').' MAD'], 'note' => $releve->note], DownloadFilename::pdf('releve-compte', $fournisseur->nom, $releve->code_client, $releve->date_releve?->format('Y-m-d') ?: (string) $releve->id));
    }

    public function pdfCheque(Fournisseur $fournisseur, FournisseurReleveCompte $releve, FournisseurCheque $cheque): Response
    {
        abort_if($cheque->fournisseur_id !== $fournisseur->id || $cheque->fournisseur_releve_compte_id !== $releve->id, 404);

        return FinancePdf::preview(['title' => ucfirst($cheque->type).' '.$cheque->numero_cheque, 'subtitle' => 'Paiement fournisseur', 'brand' => 'Droguerie Palmeraie', 'meta' => ['Fournisseur' => $fournisseur->nom, 'Releve' => $releve->code_client], 'columns' => [['key' => 'banque', 'label' => 'Banque'], ['key' => 'echeance', 'label' => 'Echeance'], ['key' => 'montant', 'label' => 'Montant', 'align' => 'right']], 'rows' => [['banque' => $cheque->banque, 'echeance' => $cheque->date_echeance?->format('d/m/Y'), 'montant' => number_format((float) $cheque->montant, 2, ',', ' ').' MAD']], 'note' => $cheque->note], DownloadFilename::pdf('cheque-fournisseur', $fournisseur->nom, $cheque->numero_cheque, $cheque->date_emission?->format('Y-m-d') ?: (string) $cheque->id));
    }

    public function serialize(Fournisseur $fournisseur): array
    {
        $due = (float) ($fournisseur->factures_sum_montant ?? 0);
        $paid = (float) ($fournisseur->cheques_sum_montant ?? 0);

        return ['id' => $fournisseur->id, 'nom' => $fournisseur->nom, 'telephone' => $fournisseur->telephone, 'ville' => $fournisseur->ville, 'note' => $fournisseur->note, 'factures_count' => (int) ($fournisseur->factures_count ?? 0), 'payments_count' => (int) ($fournisseur->cheques_count ?? 0), 'total_factures' => round($due, 2), 'total_paye' => round($paid, 2), 'balance' => round($due - $paid, 2)];
    }

    public function serializeReleve(FournisseurReleveCompte $releve): array
    {
        $due = (float) ($releve->factures_sum_montant ?? 0);
        $paid = (float) ($releve->cheques_sum_montant ?? 0);

        return ['id' => $releve->id, 'code_client' => $releve->code_client, 'date_releve' => $releve->date_releve?->format('Y-m-d'), 'note' => $releve->note, 'factures_count' => (int) ($releve->factures_count ?? 0), 'payments_count' => (int) ($releve->cheques_count ?? 0), 'total_factures' => round($due, 2), 'total_paye' => round($paid, 2), 'balance' => round($due - $paid, 2)];
    }

    private function serializeGlobalReleve(FournisseurReleveCompte $releve): array
    {
        return [...$this->serializeReleve($releve), 'fournisseur_id' => $releve->fournisseur_id, 'fournisseur_nom' => $releve->fournisseur->nom];
    }

    private function serializeCheque(FournisseurCheque $cheque): array
    {
        return ['id' => $cheque->id, 'type' => $cheque->type, 'numero_cheque' => $cheque->numero_cheque, 'banque' => $cheque->banque, 'montant' => (float) $cheque->montant, 'tireur_signataire' => $cheque->tireur_signataire, 'motif' => $cheque->motif, 'date_emission' => $cheque->date_emission?->format('Y-m-d'), 'date_echeance' => $cheque->date_echeance?->format('Y-m-d'), 'statut' => $cheque->statut, 'facture_recue' => $cheque->facture_recue, 'facture_donnee' => $cheque->facture_donnee, 'note' => $cheque->note];
    }

    private function baseQuery(array $filters): Builder
    {
        return Fournisseur::query()->withSum('factures', 'montant')->withSum('cheques', 'montant')->when($filters['search'] ?? null, fn ($query, $value) => $query->where(fn ($inner) => $inner->where('nom', 'like', "%{$value}%")->orWhere('telephone', 'like', "%{$value}%")->orWhere('ville', 'like', "%{$value}%")))->when($filters['ville'] ?? null, fn ($query, $value) => $query->where('ville', 'like', "%{$value}%"));
    }

    private function relevesQuery(array $filters): Builder
    {
        return FournisseurReleveCompte::query()->with('fournisseur:id,nom')->withSum('factures', 'montant')->withSum('cheques', 'montant')->when($filters['search'] ?? null, fn (Builder $query, string $value) => $query->where('code_client', 'like', "%{$value}%")->orWhereHas('fournisseur', fn (Builder $supplier) => $supplier->where('nom', 'like', "%{$value}%")))->when($filters['fournisseur_id'] ?? null, fn (Builder $query, $value) => $query->where('fournisseur_id', $value));
    }

    private function facturesQuery(FournisseurReleveCompte $releve, array $filters)
    {
        return $releve->factures()->when($filters['facture_search'] ?? null, fn ($query, $value) => $query->where('numero_facture', 'like', "%{$value}%"));
    }

    private function chequesQuery(FournisseurReleveCompte $releve, array $filters)
    {
        return $releve->cheques()->when($filters['payment_cheque'] ?? null, fn ($query, $value) => $query->where('numero_cheque', 'like', "%{$value}%"))->when($filters['payment_banque'] ?? null, fn ($query, $value) => $query->where('banque', 'like', "%{$value}%"))->when($filters['payment_statut'] ?? null, fn ($query, $value) => $query->where('statut', $value));
    }
}
