import { Link, router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CreatedAtFilter from '@/Components/CreatedAtFilter';
import CrudDialog from '@/Components/CrudDialog';
import ExportableDataTable from '@/Components/ExportableDataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import SearchableSelect from '@/Components/SearchableSelect';
import { articleFields } from '@/lib/articleFields';

export default function Index({ articles, filters, groups }) {
    const fields = articleFields(groups);
    const generalGroup = groups.find((group) => group.label === 'Général')?.value || '';
    const update = (key, value) => router.get(route('articles.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const columns = [
        { key: 'reference', label: 'Code', render: (row) => <Link className="font-medium text-zinc-950 hover:underline" href={route('articles.show', row.id)}>{row.reference}</Link> },
        { key: 'name', label: 'Article', render: (row) => <Link className="font-medium text-zinc-950 hover:underline" href={route('articles.show', row.id)}>{row.name}</Link> },
        { key: 'group_name', label: 'Groupe', render: (row) => row.group_name || '-' },
        { key: 'unite', label: 'Unité' },
        { key: 'total_quantity', label: 'Quantité totale' },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <CrudDialog title="Modifier article" action={route('articles.update', row.id)} method="patch" fields={fields} defaults={row} wide trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                    <DeleteButton action={route('articles.destroy', row.id)} title="Supprimer cet article ?" message="L’article sera retiré de tous les dépôts. Ses lignes d’opérations associées seront également supprimées." />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Articles" actions={<><a href={route('articles.index', { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title="Nouvel article" action={route('articles.store')} fields={fields} defaults={{ ...Object.fromEntries(fields.map((field) => [field.name, ''])), group_id: generalGroup, unite: 'U' }} wide trigger={<Button><Plus className="h-4 w-4" />Nouveau</Button>} /></>}>
            <div className="mb-4 grid max-w-4xl gap-2 md:grid-cols-3">
                <Input placeholder="Recherche code ou article" defaultValue={filters.search || ''} onChange={(event) => update('search', event.target.value)} />
                <SearchableSelect value={filters.group_id || ''} onChange={(value) => update('group_id', value)} options={groups} placeholder="Tous les groupes" />
                <CreatedAtFilter routeName="articles.index" filters={filters} />
            </div>
            <ExportableDataTable columns={columns} rows={articles.data} pagination={articles} exportUrl={route('articles.index')} exportParams={{ ...filters, export: 1 }} deleteUrl={route('articles.destroy-selected')} onRowClick={(row) => router.visit(route('articles.show', row.id))} />
        </AppLayout>
    );
}
