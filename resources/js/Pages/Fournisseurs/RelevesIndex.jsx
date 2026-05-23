import { Link, router } from '@inertiajs/react';
import { Download, FileText } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DateRangePicker from '@/Components/DateRangePicker';
import DeleteButton from '@/Components/DeleteButton';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { money } from '@/lib/utils';

export default function RelevesIndex({ releves, fournisseurs, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const update = (payload) => router.get(route('fournisseurs.releves.index'), { ...filters, ...payload }, { preserveState: true, replace: true });
    const submitSearch = (event) => {
        event.preventDefault();
        update({ search });
    };
    const clearSearch = () => {
        setSearch('');
        update({ search: '' });
    };
    const fields = [
        { name: 'code_client', label: 'Code client' },
        { name: 'date_releve', label: 'Date relevé', type: 'date' },
        { name: 'note', label: 'Note', type: 'textarea' },
    ];
    const columns = [
        {
            key: 'fournisseur_nom',
            label: 'Fournisseur',
            render: (row) => <Link className="font-medium text-zinc-950 hover:underline" href={route('fournisseurs.show', row.fournisseur_id)}>{row.fournisseur_nom}</Link>,
        },
        {
            key: 'code_client',
            label: 'Relevé compte',
            render: (row) => <Link className="font-medium text-zinc-950 hover:underline" href={route('fournisseurs.releves.show', [row.fournisseur_id, row.id])}>{row.code_client}</Link>,
        },
        { key: 'date_releve', label: 'Date relevé' },
        { key: 'total_factures', label: 'Factures', render: (row) => money(row.total_factures) },
        { key: 'total_paye', label: 'Paiements', render: (row) => money(row.total_paye) },
        { key: 'balance', label: 'Solde', render: (row) => <span className={row.balance > 0 ? 'text-red-700' : 'text-green-700'}>{money(row.balance)}</span> },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <Link href={route('fournisseurs.releves.show', [row.fournisseur_id, row.id])}><Button size="sm" variant="outline">Voir</Button></Link>
                    <a href={route('fournisseurs.releves.pdf', [row.fournisseur_id, row.id])}><Button size="sm" variant="outline"><FileText className="h-4 w-4" />PDF</Button></a>
                    <CrudDialog title="Modifier relevé compte" action={route('fournisseurs.releves.update', [row.fournisseur_id, row.id])} method="patch" fields={fields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                    <DeleteButton action={route('fournisseurs.releves.destroy', { fournisseur: row.fournisseur_id, releve: row.id, return: 'index' })} title={`Supprimer le relevé ${row.code_client} ?`} message="La suppression sera refusée tant que ce relevé contient des factures ou paiements." />
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            title="Relevés compte fournisseurs"
            actions={<><a href={route('fournisseurs.releves.index', { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><Link href={route('fournisseurs.index')}><Button variant="outline">Voir les fournisseurs</Button></Link></>}
        >
            <div className="mb-4 rounded-md border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-600">
                Tous les relevés compte sont réunis ici. Pour ajouter un relevé, ouvrez d'abord le fournisseur concerné.
            </div>
            <div className="mb-4 grid gap-2 md:grid-cols-[minmax(20rem,1fr)_260px_300px]">
                <form onSubmit={submitSearch} className="flex flex-col gap-2 sm:flex-row">
                    <Input placeholder="Rechercher fournisseur ou code relevé" value={search} onChange={(event) => setSearch(event.target.value)} />
                    <Button type="submit">Rechercher</Button>
                    {filters.search ? <Button type="button" variant="outline" onClick={clearSearch}>Effacer</Button> : null}
                </form>
                <SearchableSelect value={filters.fournisseur_id || ''} onChange={(value) => update({ fournisseur_id: value })} options={fournisseurs} placeholder="Tous les fournisseurs" />
                <DateRangePicker from={filters.date_from || ''} to={filters.date_to || ''} onChange={({ from, to }) => update({ date_from: from, date_to: to })} label="Période relevé" />
            </div>
            <DataTable
                columns={columns}
                rows={releves.data}
                pagination={releves}
                empty="Aucun relevé compte trouvé."
                onRowClick={(row) => router.visit(route('fournisseurs.releves.show', [row.fournisseur_id, row.id]))}
            />
        </AppLayout>
    );
}
