import { Link } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';

export default function Index({ depots }) {
    const fields = [{ name: 'name', label: 'Nom' }, { name: 'location', label: 'Emplacement' }];
    const columns = [
        { key: 'name', label: 'Dépôt', render: (row) => <Link className="font-medium hover:underline" href={route('depots.show', row.id)}>{row.name}</Link> },
        { key: 'location', label: 'Emplacement' },
        { key: 'total_stock', label: 'Stock total' },
        { key: 'articles_count', label: 'Articles' },
        { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-wrap gap-2"><Link href={route('depots.show', row.id)}><Button size="sm" variant="outline">Voir</Button></Link><CrudDialog title="Modifier dépôt" action={route('depots.update', row.id)} method="patch" fields={fields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('depots.destroy', row.id)} title={`Supprimer ${row.name} ?`} message="Le dépôt ne peut être supprimé que si son stock est nul et qu'il ne contient aucune opération." /></div> },
    ];

    return <AppLayout title="Dépôt" actions={<><a href={route('depots.index', { export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title="Nouveau dépôt" action={route('depots.store')} fields={fields} defaults={{ name: '', location: '' }} trigger={<Button><Plus className="h-4 w-4" />Nouveau</Button>} /></>}><DataTable columns={columns} rows={depots} pagination={{ links: [] }} /></AppLayout>;
}
