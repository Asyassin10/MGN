import { Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';

const groupFields = [{ name: 'name', label: 'Group name (اسم العائلة)' }];

export default function Index({ groups }) {
    const columns = [
        { key: 'name', label: 'Group (العائلة)', render: (row) => <Link className="font-medium text-zinc-950 hover:underline" href={route('groupes.show', row.id)}>{row.name}</Link> },
        { key: 'articles_count', label: 'Articles' },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <Link href={route('groupes.show', row.id)}><Button size="sm">Ouvrir / assigner des articles</Button></Link>
                    {row.protected ? null : <>
                        <CrudDialog title="Modifier le groupe" action={route('groupes.update', row.id)} method="patch" fields={groupFields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                        <DeleteButton action={route('groupes.destroy', row.id)} title={`Supprimer ${row.name} ?`} message="Les articles de ce groupe reviendront dans le groupe Général." />
                    </>}
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Groupes d'articles" actions={<CrudDialog title="Nouveau groupe" action={route('groupes.store')} fields={groupFields} defaults={{ name: '' }} submitLabel="Créer le groupe" trigger={<Button><Plus className="h-4 w-4" />Nouveau groupe</Button>} />}>
            <DataTable columns={columns} rows={groups} pagination={{ links: [] }} empty="Aucun groupe." onRowClick={(row) => router.visit(route('groupes.show', row.id))} />
        </AppLayout>
    );
}
