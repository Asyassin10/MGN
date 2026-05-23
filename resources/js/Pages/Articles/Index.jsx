import { router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const fields = [
    { name: 'reference', label: 'Code' },
    { name: 'name', label: 'Article' },
];

export default function Index({ articles, filters }) {
    const update = (key, value) => router.get(route('articles.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const columns = [
        { key: 'reference', label: 'Code' },
        { key: 'name', label: 'Article' },
        { key: 'depots_count', label: 'Dépôts assignés' },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <CrudDialog title="Modifier article" action={route('articles.update', row.id)} method="patch" fields={fields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                    <DeleteButton action={route('articles.destroy', row.id)} title="Supprimer cet article ?" message="La suppression sera refusée si cet article a du stock ou figure dans une opération." />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Articles" actions={<><a href={route('articles.index', { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title="Nouvel article" action={route('articles.store')} fields={fields} defaults={{ reference: '', name: '' }} trigger={<Button><Plus className="h-4 w-4" />Nouveau</Button>} /></>}>
            <div className="mb-4 max-w-md">
                <Input placeholder="Recherche code ou article" defaultValue={filters.search || ''} onChange={(event) => update('search', event.target.value)} />
            </div>
            <DataTable columns={columns} rows={articles.data} pagination={articles} />
        </AppLayout>
    );
}
