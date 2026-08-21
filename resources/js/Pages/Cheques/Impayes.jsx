import { router } from '@inertiajs/react';
import { Banknote, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import SearchableSelect from '@/Components/SearchableSelect';
import { Card, CardContent } from '@/Components/ui/card';
import { money } from '@/lib/utils';

const types = [{ value: 'cheque', label: 'Chèque' }, { value: 'effet', label: 'Effet' }];
const statuses = [{ value: 'impaye', label: 'Impayé' }, { value: 'paye', label: 'Payé' }];
const paymentModes = [{ value: 'espece', label: 'Espèce' }, { value: 'virement', label: 'Virement' }, { value: 'cheque', label: 'Chèque' }];
const fields = [
    { name: 'type', label: 'Type', type: 'select', options: types },
    { name: 'numero_cheque', label: 'N° chèque' },
    { name: 'fournisseur_nom', label: 'Nom du fournisseur' },
    { name: 'client_nom', label: 'Nom du client' },
    { name: 'tireur_signataire', label: 'Tireur / signataire' },
    { name: 'date_remise', label: 'Date de remise par le fournisseur', type: 'date' },
    { name: 'montant', label: 'Montant DH', type: 'number' },
    { name: 'note', label: 'Note', type: 'textarea' },
];

export default function Impayes({ cheques, filters, impayesCount }) {
    const updateFilters = (changes) => router.get(route('cheques.impayes.index'), { ...filters, ...changes }, { preserveState: true, replace: true });
    const defaults = { type: 'cheque', numero_cheque: '', fournisseur_nom: '', client_nom: '', tireur_signataire: '', date_remise: '', montant: '', note: '' };
    const editFields = [...fields, { name: 'statut', label: 'Statut', type: 'select', options: statuses }, { name: 'date_paiement', label: 'Date réelle du paiement', type: 'date' }, { name: 'mode_paiement', label: 'Mode de paiement', type: 'select', options: paymentModes }];

    const columns = [
        { key: 'numero_cheque', label: 'N° chèque' },
        { key: 'type', label: 'Type', render: (row) => row.type === 'cheque' ? 'Chèque' : 'Effet' },
        { key: 'fournisseur_nom', label: 'Fournisseur' },
        { key: 'client_nom', label: 'Client' },
        { key: 'tireur_signataire', label: 'Tireur / signataire' },
        { key: 'date_remise', label: 'Date de remise' },
        { key: 'montant', label: 'Montant', render: (row) => money(row.montant) },
        { key: 'statut', label: 'Statut', render: (row) => row.statut === 'paye' ? 'Payé' : 'Impayé' },
        { key: 'date_paiement', label: 'Date de paiement', render: (row) => row.date_paiement || '—' },
        { key: 'mode_paiement', label: 'Mode de paiement', render: (row) => paymentModes.find((mode) => mode.value === row.mode_paiement)?.label || '—' },
        { key: 'note', label: 'Note' },
        { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-nowrap items-center gap-1 whitespace-nowrap">{row.statut === 'impaye' ? <CrudDialog title="Enregistrer le paiement" action={route('cheques.impayes.pay', row.id)} method="patch" fields={[{ name: 'date_paiement', label: 'Date réelle du paiement', type: 'date' }, { name: 'mode_paiement', label: 'Mode de paiement', type: 'select', options: paymentModes }]} defaults={{ date_paiement: '', mode_paiement: 'espece' }} submitLabel="Confirmer le paiement" trigger={<Button size="sm" className="h-8 gap-1 px-2 text-xs"><Banknote className="h-3.5 w-3.5" />Payer</Button>} /> : null}<CrudDialog title="Modifier le chèque impayé" action={route('cheques.impayes.update', row.id)} method="patch" fields={editFields} defaults={{ ...row, date_paiement: row.date_paiement || '', mode_paiement: row.mode_paiement || '' }} trigger={<Button size="sm" variant="outline" className="h-8 px-2 text-xs">Modifier</Button>} /><DeleteButton action={route('cheques.impayes.destroy', row.id)} title="Supprimer ce chèque impayé ?" className={`h-8 gap-1 px-2 text-xs ${row.statut === 'impaye' ? 'text-white hover:bg-red-700 hover:text-white' : ''}`} /></div> },
    ];

    return <AppLayout title="Impayés" actions={<CrudDialog title="Ajouter un chèque impayé" action={route('cheques.impayes.store')} fields={fields} defaults={defaults} trigger={<Button><Plus className="h-4 w-4" />Ajouter un impayé</Button>} />}>
        <div className="mb-4"><Card className="max-w-sm"><CardContent><div className="text-sm text-zinc-500">Nombre de chèques impayés</div><div className="mt-1 text-2xl font-semibold text-red-700">{impayesCount}</div></CardContent></Card></div>
        <div className="mb-4 grid gap-2 md:grid-cols-3">
            <Input placeholder="N° chèque, fournisseur, client ou signataire" defaultValue={filters.search || ''} onChange={(event) => updateFilters({ search: event.target.value })} />
            <SearchableSelect value={filters.type || ''} onChange={(type) => updateFilters({ type })} options={types} placeholder="Tous les types" />
            <SearchableSelect value={filters.statut || ''} onChange={(statut) => updateFilters({ statut })} options={statuses} placeholder="Tous les statuts" />
        </div>
        <DataTable columns={columns} rows={cheques.data} pagination={cheques} rowClassName={(row) => row.statut === 'paye' ? 'status-row-complete' : 'status-row status-row-sorti'} empty="Aucun chèque impayé." />
    </AppLayout>;
}
