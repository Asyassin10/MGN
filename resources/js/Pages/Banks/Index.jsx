import { router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

export default function Index({ banks, filters }) {
    const update = (key, value) => router.get(route('banks.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const fields = [{ name: 'name', label: 'Nom de banque' }];
    const columns = [
        { key: 'name', label: 'Banque' },
        { key: 'cheque_clients_count', label: 'Chèques clients' },
        { key: 'cheque_fournisseurs_count', label: 'Chèques fournisseurs' },
        {
            key: 'actions',
            label: 'Actions',
            render: (bank) => (
                <div className="flex flex-wrap gap-2">
                    <CrudDialog title="Modifier banque" action={route('banks.update', bank.id)} method="patch" fields={fields} defaults={{ name: bank.name }} trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                    <DeleteButton action={route('banks.destroy', bank.id)} title="Supprimer cette banque ?" message="La suppression sera refusée si des chèques utilisent cette banque." />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Banques" actions={<><a href={route('banks.index', { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title="Ajouter banque" action={route('banks.store')} fields={fields} defaults={{ name: '' }} trigger={<Button><Plus className="h-4 w-4" />Nouvelle banque</Button>} /></>}>
            <div className="mb-4 max-w-sm">
                <Input placeholder="Recherche banque" defaultValue={filters.search || ''} onChange={(event) => update('search', event.target.value)} />
            </div>
            <DataTable columns={columns} rows={banks.data} pagination={banks} />
        </AppLayout>
    );
}
