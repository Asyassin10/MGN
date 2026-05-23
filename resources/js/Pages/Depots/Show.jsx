import { router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

const adjustmentTypes = [{ value: 'add', label: 'Ajouter' }, { value: 'subtract', label: 'Retirer' }];

export default function Show({ depot, articles, filters, articleOptions }) {
    const fields = [{ name: 'article_id', label: 'Article', type: 'select', options: articleOptions }, { name: 'adjustment_type', label: 'Type', type: 'select', options: adjustmentTypes }, { name: 'quantity', label: 'Quantité', type: 'number' }];
    const depotFields = [{ name: 'name', label: 'Nom' }, { name: 'location', label: 'Emplacement' }];
    return (
        <AppLayout title={depot.name} actions={<><a href={route('depots.show', { depot: depot.id, ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title="Modifier dépôt" action={route('depots.update', depot.id)} method="patch" fields={depotFields} defaults={depot} trigger={<Button variant="outline">Modifier</Button>} /><CrudDialog title="Ajuster stock" action={route('depots.adjust-stock', depot.id)} fields={fields} defaults={{ article_id: '', adjustment_type: 'add', quantity: 1 }} trigger={<Button><Plus className="h-4 w-4" />Ajuster</Button>} /><DeleteButton action={route('depots.destroy', depot.id)} title={`Supprimer ${depot.name} ?`} message="Le dépôt ne peut être supprimé que si son stock est nul et qu'il ne contient aucune opération." /></>}>
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Emplacement</div><div className="mt-2 font-medium">{depot.location || '-'}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Stock total</div><div className="mt-2 font-medium">{depot.total_stock}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Articles</div><div className="mt-2 font-medium">{depot.articles_count}</div></CardContent></Card>
            </div>
            <div className="mb-4 max-w-sm"><Input placeholder="Rechercher article" defaultValue={filters.search || ''} onChange={(e) => router.get(route('depots.show', depot.id), { search: e.target.value }, { preserveState: true, replace: true })} /></div>
            <DataTable columns={[{ key: 'reference', label: 'Référence' }, { key: 'name', label: 'Article', render: (row) => row.name || '-' }, { key: 'quantity', label: 'Quantité' }]} rows={articles.data} pagination={articles} />
        </AppLayout>
    );
}
