import { Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import PrintPdfButton from '@/Components/PrintPdfButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { money } from '@/lib/utils';

export default function Index({ bons, filters }) {
    const columns = [
        { key: 'created_at', label: 'Date / heure' },
        { key: 'reference', label: 'Référence' },
        { key: 'client', label: 'Client', render: (row) => row.client || '-' },
        { key: 'depots', label: 'Dépôt(s)' },
        { key: 'employee', label: 'Livreur', render: (row) => row.employee || '-' },
        { key: 'mode', label: 'Mode de paiement' },
        { key: 'lines_count', label: 'Lignes' },
        { key: 'total', label: 'Total', render: (row) => <span className="text-lg font-semibold">{money(row.total)}</span> },
        { key: 'created_by', label: 'Créé par', render: (row) => row.created_by || '-' },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <PrintPdfButton url={row.pdf_url} size="sm" />
                    <DeleteButton action={route('livraisons.destroy', row.id)} title={`Supprimer ${row.reference} ?`} message="Le stock sorti par ce bon de livraison sera rétabli dans les dépôts." />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Bons de livraison" actions={<Link href={route('livraisons.create')}><Button><Plus className="h-4 w-4" />Nouveau bon</Button></Link>}>
            <div className="mb-4 max-w-md">
                <Input placeholder="Référence ou client" defaultValue={filters.search || ''} onChange={(event) => router.get(route('livraisons.index'), { search: event.target.value }, { preserveState: true, replace: true })} />
            </div>
            <DataTable columns={columns} rows={bons.data} pagination={bons} empty="Aucun bon de livraison." />
        </AppLayout>
    );
}
