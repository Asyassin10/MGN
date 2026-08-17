import { Link, router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const fields = [
    { name: 'name', label: 'Nom complet' },
    { name: 'telephone', label: 'Téléphone' },
    { name: 'salary', label: 'Salaire DH', type: 'number' },
    { name: 'salary_payment_day', label: 'Jour paiement', type: 'number' },
    { name: 'status', label: 'Statut', type: 'select', options: [{ value: 'active', label: 'Actif' }, { value: 'inactive', label: 'Inactif' }] },
];

export default function Index({ employees, filters }) {
    const update = (key, value) => router.get(route('employees.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const columns = [
        { key: 'name', label: 'Nom complet', render: (row) => <Link className="font-medium text-zinc-950 hover:underline" href={route('employees.show', row.id)}>{row.name}</Link> },
        { key: 'telephone', label: 'Téléphone' },
        { key: 'salary', label: 'Salaire', render: (row) => `${row.salary} DH` },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <Link href={route('employees.show', row.id)}><Button size="sm">Gérer paie & présence</Button></Link>
                    <CrudDialog title="Modifier employé" action={route('employees.update', row.id)} method="patch" fields={fields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                    <DeleteButton action={route('employees.destroy', row.id)} title="Supprimer cet employé ?" message="La suppression sera refusée si des opérations sont associées à cet employé." />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="RH / Employés" actions={<><a href={route('employees.index', { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title="Nouvel employé" action={route('employees.store')} fields={fields} defaults={{ name: '', telephone: '', salary: '', salary_payment_day: 1, status: 'active' }} trigger={<Button><Plus className="h-4 w-4" />Nouveau</Button>} /></>}>
            <div className="mb-4">
                <Input placeholder="Recherche nom" defaultValue={filters.search || ''} onChange={(event) => update('search', event.target.value)} />
            </div>
            <DataTable columns={columns} rows={employees.data} pagination={employees} onRowClick={(row) => router.visit(route('employees.show', row.id))} />
        </AppLayout>
    );
}
