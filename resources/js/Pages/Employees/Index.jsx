import { router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const fields = [
    { name: 'name', label: 'Nom' },
    { name: 'prenom', label: 'Prénom' },
    { name: 'poste', label: 'Poste' },
    { name: 'telephone', label: 'Téléphone' },
];

export default function Index({ employees, filters }) {
    const update = (key, value) => router.get(route('employees.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const columns = [
        { key: 'name', label: 'Nom' },
        { key: 'prenom', label: 'Prénom' },
        { key: 'poste', label: 'Poste' },
        { key: 'telephone', label: 'Téléphone' },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <CrudDialog title="Modifier employé" action={route('employees.update', row.id)} method="patch" fields={fields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                    <DeleteButton action={route('employees.destroy', row.id)} title="Supprimer cet employé ?" message="La suppression sera refusée si des opérations sont associées à cet employé." />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Employés" actions={<><a href={route('employees.index', { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title="Nouvel employé" action={route('employees.store')} fields={fields} defaults={{ name: '', prenom: '', poste: '', telephone: '' }} trigger={<Button><Plus className="h-4 w-4" />Nouveau</Button>} /></>}>
            <div className="mb-4 grid gap-2 md:grid-cols-2">
                <Input placeholder="Recherche nom" defaultValue={filters.search || ''} onChange={(event) => update('search', event.target.value)} />
                <Input placeholder="Poste" defaultValue={filters.poste || ''} onChange={(event) => update('poste', event.target.value)} />
            </div>
            <DataTable columns={columns} rows={employees.data} pagination={employees} />
        </AppLayout>
    );
}
