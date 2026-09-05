import { router } from '@inertiajs/react';
import { Download, LogOut, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent } from '@/Components/ui/card';
import { money } from '@/lib/utils';
import { getChequeRowClass } from '@/lib/chequeStatus';

const types = [{ value: 'cheque', label: 'Chèque' }, { value: 'effet', label: 'Effet' }];
const statuses = [{ value: 'en_cours', label: 'En cours' }, { value: 'en_caisse', label: 'En caisse' }, { value: 'impaye', label: 'Impayé' }];
const fields = [
    { name: 'type', label: 'Type', type: 'select', options: types },
    { name: 'numero_cheque', label: 'N° chèque' },
    { name: 'client_nom', label: 'Nom du client' },
    { name: 'tireur_signataire', label: 'Tireur / signataire' },
    { name: 'date_emission', label: 'Date d’émission', type: 'date' },
    { name: 'date_echeance', label: 'Date d’échéance', type: 'date' },
    { name: 'statut', label: 'Statut', type: 'select', options: statuses },
    { name: 'montant', label: 'Montant DH', type: 'number' },
    { name: 'note', label: 'Note', type: 'textarea' },
];

export default function Index({ cheques, filters, montantDisponible, chequesDisponiblesCount }) {
    const [selectedRows, setSelectedRows] = useState({});
    const updateFilters = (changes) => router.get(route('cheques.index'), { ...filters, ...changes }, { preserveState: true, replace: true });
    const inlineUpdate = (row, changes) => router.patch(route('cheques.inline', row.id), changes, { preserveScroll: true, preserveState: true });
    const defaults = { type: 'cheque', numero_cheque: '', client_nom: '', tireur_signataire: '', date_emission: '', date_echeance: '', statut: 'en_cours', montant: '', note: '' };
    const sortieFields = [{ name: 'est_sorti', label: 'Chèque sorti ?', type: 'select', options: [{ value: '1', label: 'Oui' }, { value: '0', label: 'Non' }] }, { name: 'fournisseur_sortie_nom', label: 'Nom du fournisseur' }];
    const selectedIds = useMemo(() => Object.keys(selectedRows).map(Number), [selectedRows]);
    const pageIds = cheques.data.map((row) => row.id);
    const allPageRowsSelected = pageIds.length > 0 && pageIds.every((id) => selectedIds.includes(id));
    const selectedTotal = useMemo(() => Object.values(selectedRows)
        .reduce((total, row) => total + Number(row.montant || 0), 0), [selectedRows]);
    const toggleRow = (row) => setSelectedRows((current) => {
        const next = { ...current };
        if (next[row.id]) delete next[row.id];
        else next[row.id] = row;
        return next;
    });
    const togglePage = () => setSelectedRows((current) => {
        const next = { ...current };
        cheques.data.forEach((row) => {
            if (allPageRowsSelected) delete next[row.id];
            else next[row.id] = row;
        });
        return next;
    });
    const downloadExcel = (ids = []) => window.location.assign(route('cheques.export', { ...filters, ...(ids.length ? { selected_ids: ids } : {}) }));

    const columns = [
        { key: 'selection', label: <input aria-label="Sélectionner tous les chèques affichés" type="checkbox" checked={allPageRowsSelected} onChange={togglePage} />, render: (row) => <input aria-label={`Sélectionner le chèque ${row.numero_cheque}`} type="checkbox" checked={selectedIds.includes(row.id)} onClick={(event) => event.stopPropagation()} onChange={() => toggleRow(row)} /> },
        { key: 'numero_cheque', label: 'N° chèque' },
        { key: 'type', label: 'Type', render: (row) => row.type === 'cheque' ? 'Chèque' : 'Effet' },
        { key: 'client_nom', label: 'Client' },
        { key: 'tireur_signataire', label: 'Tireur / signataire' },
        { key: 'montant', label: 'Montant', render: (row) => money(row.montant) },
        { key: 'date_emission', label: 'Émission' },
        { key: 'date_echeance', label: 'Échéance' },
        { key: 'statut', label: 'Statut', render: (row) => <SearchableSelect value={row.statut} onChange={(statut) => inlineUpdate(row, { statut })} options={statuses} allowEmpty={false} /> },
        { key: 'est_sorti', label: 'Sorti ?', render: (row) => row.est_sorti ? 'Oui' : 'Non' },
        { key: 'fournisseur_sortie_nom', label: 'Fournisseur', render: (row) => row.fournisseur_sortie_nom || '—' },
        { key: 'note', label: 'Note' },
        { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-nowrap items-center gap-1 whitespace-nowrap"><CrudDialog title="Sortie du chèque" action={route('cheques.sortie', row.id)} method="patch" fields={sortieFields} defaults={{ est_sorti: row.est_sorti ? '1' : '0', fournisseur_sortie_nom: row.fournisseur_sortie_nom || '' }} trigger={<Button size="sm" className="h-8 gap-1 px-2 text-xs"><LogOut className="h-3.5 w-3.5" />Sortie</Button>} /><CrudDialog title="Modifier le chèque" action={route('cheques.update', row.id)} method="patch" fields={fields} defaults={row} trigger={<Button size="sm" variant="outline" className="h-8 px-2 text-xs">Modifier</Button>} /><DeleteButton action={route('cheques.destroy', row.id)} title="Supprimer ce chèque ?" className={`h-8 gap-1 px-2 text-xs ${row.est_sorti ? 'text-white hover:bg-red-700 hover:text-white' : ''}`} /></div> },
    ];

    return <AppLayout title="Chèques" actions={<><Button variant="outline" onClick={() => downloadExcel()}><Download className="h-4 w-4" />Exporter Excel</Button><CrudDialog title="Ajouter un chèque" action={route('cheques.store')} fields={fields} defaults={defaults} trigger={<Button><Plus className="h-4 w-4" />Ajouter un chèque</Button>} /></>}>
        <div className="mb-4 grid gap-3 md:grid-cols-2"><Card><CardContent><div className="text-sm text-zinc-500">Montant total disponible</div><div className="mt-1 text-2xl font-semibold text-emerald-700">{money(montantDisponible)}</div></CardContent></Card><Card><CardContent><div className="text-sm text-zinc-500">Chèques non sortis</div><div className="mt-1 text-2xl font-semibold text-emerald-700">{chequesDisponiblesCount}</div></CardContent></Card></div>
        <div className="mb-4 grid gap-2 md:grid-cols-5">
            <Input placeholder="N° chèque, client ou signataire" defaultValue={filters.search || ''} onChange={(event) => updateFilters({ search: event.target.value })} />
            <Input placeholder="Rechercher fournisseur" defaultValue={filters.fournisseur || ''} onChange={(event) => updateFilters({ fournisseur: event.target.value })} />
            <SearchableSelect value={filters.type || ''} onChange={(type) => updateFilters({ type })} options={types} placeholder="Tous les types" />
            <SearchableSelect value={filters.statut || ''} onChange={(statut) => updateFilters({ statut })} options={statuses} placeholder="Tous les statuts" />
            <SearchableSelect value={filters.sortie || ''} onChange={(sortie) => updateFilters({ sortie })} options={[{ value: '1', label: 'Oui' }, { value: '0', label: 'Non' }]} placeholder="Tous les chèques" />
        </div>
        {selectedIds.length ? <div className="mb-4 flex flex-wrap items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-emerald-900"><Button size="sm" onClick={() => downloadExcel(selectedIds)}><Download className="h-4 w-4" />Exporter la sélection</Button><span className="text-base font-bold md:text-lg">{selectedIds.length} chèque(s) sélectionné(s) · Total : {money(selectedTotal)}</span></div> : null}
        <DataTable columns={columns} rows={cheques.data} pagination={cheques} rowClassName={(row) => row.est_sorti ? 'status-row status-row-sorti' : getChequeRowClass(row)} empty="Aucun chèque." />
    </AppLayout>;
}
