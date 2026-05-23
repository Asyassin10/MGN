import { Link, router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import DateRangePicker from '@/Components/DateRangePicker';
import DataTable from '@/Components/DataTable';
import SearchableSelect from '@/Components/SearchableSelect';
import StatusBadge from '@/Components/StatusBadge';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { money } from '@/lib/utils';

const statuses = [{ value: 'en_cours', label: 'En cours' }, { value: 'en_caisse', label: 'En caisse' }, { value: 'impaye', label: 'Impayé' }];

export default function PartyChequeIndex({ title, routeName, ownerKey, ownerLabel, ownerColumn, owners, cheques, filters, banques }) {
    const update = (key, value) => router.get(route(`${routeName}.index`), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const columns = [
        { key: 'numero_cheque', label: 'Numéro' },
        { key: 'type', label: 'Type' },
        { key: ownerColumn, label: ownerLabel },
        { key: 'banque', label: 'Banque' },
        { key: 'montant', label: 'Montant', render: (r) => money(r.montant) },
        { key: 'date_emission', label: 'Émission' },
        { key: 'date_echeance', label: 'Échéance' },
        { key: 'statut', label: 'Statut', render: (r) => <div className="grid min-w-36 gap-1"><StatusBadge statut={r.statut} /><SearchableSelect value={r.statut} onChange={(value) => router.patch(route(`${routeName}.status`, r.id), { statut: value }, { preserveScroll: true })} options={statuses} allowEmpty={false} /></div> },
        { key: 'actions', label: 'Actions', render: (r) => <div className="flex flex-wrap gap-2"><Link href={route(`${routeName}.show`, r.id)}><Button size="sm" variant="outline">Voir</Button></Link><Link href={route(`${routeName}.edit`, r.id)}><Button size="sm" variant="outline">Modifier</Button></Link><DeleteButton action={route(`${routeName}.destroy`, r.id)} title={`Supprimer le chèque ${r.numero_cheque} ?`} /></div> },
    ];

    return (
        <AppLayout title={title} actions={<><a href={route(`${routeName}.index`, { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><Link href={route(`${routeName}.create`)}><Button><Plus className="h-4 w-4" />Nouveau</Button></Link></>}>
            <div className="mb-4 grid gap-2 md:grid-cols-5 xl:grid-cols-5">
                <Input placeholder="Recherche" defaultValue={filters.search || ''} onChange={(e) => update('search', e.target.value)} />
                <SearchableSelect value={filters[ownerKey] || ''} onChange={(value) => update(ownerKey, value)} options={owners} placeholder={ownerLabel} />
                <SearchableSelect value={filters.statut || ''} onChange={(value) => update('statut', value)} options={statuses} placeholder="Statut" />
                <SearchableSelect value={filters.banque || ''} onChange={(value) => update('banque', value)} options={banques} placeholder="Banque" />
                <DateRangePicker from={filters.date_echeance_from || ''} to={filters.date_echeance_to || ''} onChange={({ from, to }) => router.get(route(`${routeName}.index`), { ...filters, date_echeance_from: from, date_echeance_to: to, date_emission_from: '', date_emission_to: '' }, { preserveState: true, replace: true })} label="Échéance" />
            </div>
            <DataTable columns={columns} rows={cheques.data} pagination={cheques} />
        </AppLayout>
    );
}
