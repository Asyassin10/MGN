import { router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import InvoiceReceiptSelect from '@/Components/InvoiceReceiptSelect';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
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
    { name: 'facture_recue', label: 'Facture reçue', type: 'checkbox' },
    { name: 'facture_donnee', label: 'Facture donnée', type: 'checkbox' },
    { name: 'montant', label: 'Montant DH', type: 'number' },
    { name: 'note', label: 'Note', type: 'textarea' },
];

export default function Index({ cheques, filters }) {
    const updateFilters = (changes) => router.get(route('cheques.index'), { ...filters, ...changes }, { preserveState: true, replace: true });
    const inlineUpdate = (row, changes) => router.patch(route('cheques.inline', row.id), changes, { preserveScroll: true, preserveState: true });
    const defaults = { type: 'cheque', numero_cheque: '', client_nom: '', tireur_signataire: '', date_emission: '', date_echeance: '', statut: 'en_cours', facture_recue: false, facture_donnee: false, montant: '', note: '' };

    const columns = [
        { key: 'numero_cheque', label: 'N° chèque' },
        { key: 'type', label: 'Type', render: (row) => row.type === 'cheque' ? 'Chèque' : 'Effet' },
        { key: 'client_nom', label: 'Client' },
        { key: 'tireur_signataire', label: 'Tireur / signataire' },
        { key: 'montant', label: 'Montant', render: (row) => money(row.montant) },
        { key: 'date_emission', label: 'Émission' },
        { key: 'date_echeance', label: 'Échéance' },
        { key: 'statut', label: 'Statut', render: (row) => <SearchableSelect value={row.statut} onChange={(statut) => inlineUpdate(row, { statut })} options={statuses} allowEmpty={false} /> },
        { key: 'facture_recue', label: 'Facture reçue', render: (row) => <InvoiceReceiptSelect value={row.facture_recue} onChange={(facture_recue) => inlineUpdate(row, { facture_recue })} /> },
        { key: 'facture_donnee', label: 'Facture donnée', render: (row) => <InvoiceReceiptSelect value={row.facture_donnee} onChange={(facture_donnee) => inlineUpdate(row, { facture_donnee })} label="Facture donnée" /> },
        { key: 'note', label: 'Note' },
        { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-wrap gap-2"><CrudDialog title="Modifier le chèque" action={route('cheques.update', row.id)} method="patch" fields={fields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('cheques.destroy', row.id)} title="Supprimer ce chèque ?" /></div> },
    ];

    return <AppLayout title="Chèques" actions={<CrudDialog title="Ajouter un chèque" action={route('cheques.store')} fields={fields} defaults={defaults} trigger={<Button><Plus className="h-4 w-4" />Ajouter un chèque</Button>} />}>
        <div className="mb-4 grid gap-2 md:grid-cols-3">
            <Input placeholder="N° chèque, client ou signataire" defaultValue={filters.search || ''} onChange={(event) => updateFilters({ search: event.target.value })} />
            <SearchableSelect value={filters.type || ''} onChange={(type) => updateFilters({ type })} options={types} placeholder="Tous les types" />
            <SearchableSelect value={filters.statut || ''} onChange={(statut) => updateFilters({ statut })} options={statuses} placeholder="Tous les statuts" />
        </div>
        <DataTable columns={columns} rows={cheques.data} pagination={cheques} rowClassName={getChequeRowClass} empty="Aucun chèque." />
    </AppLayout>;
}
