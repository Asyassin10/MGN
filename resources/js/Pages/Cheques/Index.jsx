import { Link, router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import DataTable from '@/Components/DataTable';
import SearchableSelect from '@/Components/SearchableSelect';
import StatusBadge from '@/Components/StatusBadge';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { money } from '@/lib/utils';

const types = [{ value: 'client', label: 'Client' }, { value: 'fournisseur', label: 'Fournisseur' }];
const statuses = [{ value: 'en_cours', label: 'En cours' }, { value: 'encaisse', label: 'Encaissé' }, { value: 'impaye', label: 'Impayé' }];

export default function Index({ cheques, filters, banques }) {
    const update = (key, value) => router.get(route('cheques.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const columns = [
        { key: 'numero_cheque', label: 'Numéro' },
        { key: 'type', label: 'Type' },
        { key: 'tier', label: 'Tier' },
        { key: 'banque', label: 'Banque' },
        { key: 'montant', label: 'Montant', render: (r) => money(r.montant) },
        { key: 'date_emission', label: 'Émission' },
        { key: 'date_echeance', label: 'Échéance' },
        { key: 'statut', label: 'Statut', render: (r) => <div className="grid min-w-36 gap-1"><StatusBadge statut={r.statut} /><SearchableSelect value={r.statut} onChange={(value) => router.patch(route('cheques.status', r.id), { statut: value }, { preserveScroll: true })} options={statuses} allowEmpty={false} /></div> },
        { key: 'actions', label: 'Actions', render: (r) => <div className="flex flex-wrap gap-2"><Link href={route('cheques.show', r.id)}><Button size="sm" variant="outline">Voir</Button></Link><Link href={route('cheques.edit', r.id)}><Button size="sm" variant="outline">Modifier</Button></Link><DeleteButton action={route('cheques.destroy', r.id)} title={`Supprimer le chèque ${r.numero_cheque} ?`} /></div> },
    ];

    return (
        <AppLayout title="Chèques" actions={<><a href={route('cheques.index', { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><Link href={route('cheques.create')}><Button><Plus className="h-4 w-4" />Nouveau</Button></Link></>}>
            <div className="mb-4 grid gap-2 md:grid-cols-4 xl:grid-cols-8">
                <SearchableSelect value={filters.type || ''} onChange={(value) => update('type', value)} options={types} placeholder="Type" />
                <SearchableSelect value={filters.statut || ''} onChange={(value) => update('statut', value)} options={statuses} placeholder="Statut" />
                <SearchableSelect value={filters.banque || ''} onChange={(value) => update('banque', value)} options={banques} placeholder="Banque" />
                <Input type="date" defaultValue={filters.date_from || ''} onChange={(e) => update('date_from', e.target.value)} />
                <Input type="date" defaultValue={filters.date_to || ''} onChange={(e) => update('date_to', e.target.value)} />
                <Input type="number" placeholder="Min" defaultValue={filters.montant_min || ''} onChange={(e) => update('montant_min', e.target.value)} />
                <Input type="number" placeholder="Max" defaultValue={filters.montant_max || ''} onChange={(e) => update('montant_max', e.target.value)} />
                <Input placeholder="Recherche" defaultValue={filters.search || ''} onChange={(e) => update('search', e.target.value)} />
            </div>
            <DataTable columns={columns} rows={cheques.data} pagination={cheques} />
        </AppLayout>
    );
}
