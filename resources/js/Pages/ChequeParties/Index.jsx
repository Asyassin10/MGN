import { router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

export default function Index({ title, routeName, parties, filters }) {
    const update = (key, value) => router.get(route(`${routeName}.index`), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const fields = [
        { name: 'nom', label: 'Nom' },
        { name: 'telephone', label: 'Téléphone' },
        { name: 'email', label: 'Email' },
    ];
    const columns = [
        { key: 'nom', label: 'Nom' },
        { key: 'telephone', label: 'Téléphone' },
        { key: 'email', label: 'Email' },
        { key: 'cheques_count', label: 'Chèques' },
        {
            key: 'actions',
            label: 'Actions',
            render: (party) => (
                <div className="flex flex-wrap gap-2">
                    <CrudDialog title={`Modifier ${party.nom}`} action={route(`${routeName}.update`, party.id)} method="patch" fields={fields} defaults={{ nom: party.nom, telephone: party.telephone || '', email: party.email || '' }} trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                    <DeleteButton action={route(`${routeName}.destroy`, party.id)} title={`Supprimer ${party.nom} ?`} message="La suppression sera refusée si des chèques sont associés à ce tiers." />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title={title} actions={<><a href={route(`${routeName}.index`, { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title={`Ajouter ${title.toLowerCase()}`} action={route(`${routeName}.store`)} fields={fields} defaults={{ nom: '', telephone: '', email: '' }} trigger={<Button><Plus className="h-4 w-4" />Nouveau</Button>} /></>}>
            <div className="mb-4 max-w-sm">
                <Input placeholder="Recherche nom, téléphone, email" defaultValue={filters.search || ''} onChange={(event) => update('search', event.target.value)} />
            </div>
            <DataTable columns={columns} rows={parties.data} pagination={parties} />
        </AppLayout>
    );
}
