import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';

const fields = [{ name: 'reference', label: 'Code' }, { name: 'name', label: 'Article' }];

export default function Show({ article, depots, operations }) {
    return <AppLayout title={article.name} actions={<><Link href={route('articles.index')}><Button variant="outline"><ArrowLeft className="h-4 w-4" />Retour aux articles</Button></Link><CrudDialog title="Modifier article" action={route('articles.update', article.id)} method="patch" fields={fields} defaults={article} trigger={<Button variant="outline">Modifier</Button>} /><DeleteButton action={route('articles.destroy', article.id)} title="Supprimer cet article ?" message="La suppression sera refusée si cet article a du stock ou figure dans une opération." /></>}>
        <div className="mb-5 grid gap-4 md:grid-cols-3">
            <Card><CardContent><div className="text-sm uppercase text-zinc-500">Code article</div><div className="mt-2 font-medium">{article.reference}</div></CardContent></Card>
            <Card><CardContent><div className="text-sm uppercase text-zinc-500">Article</div><div className="mt-2 font-medium">{article.name}</div></CardContent></Card>
            <Card><CardContent><div className="text-sm uppercase text-zinc-500">Quantité totale</div><div className="mt-2 text-2xl font-semibold">{article.total_quantity}</div></CardContent></Card>
        </div>

        <div className="mb-6"><h2 className="mb-3 text-lg font-semibold text-zinc-950">Stock par dépôt</h2><DataTable columns={[{ key: 'name', label: 'Dépôt', render: (row) => <Link className="font-medium hover:underline" href={route('depots.show', row.id)}>{row.name}</Link> }, { key: 'location', label: 'Emplacement', render: (row) => row.location || '—' }, { key: 'quantity', label: 'Quantité' }]} rows={depots} pagination={{ links: [] }} empty="Cet article n’est assigné à aucun dépôt." /></div>

        <div><h2 className="mb-3 text-lg font-semibold text-zinc-950">Opérations de cet article</h2><DataTable columns={[{ key: 'reference', label: 'Référence', render: (row) => row.operation_id ? <Link className="font-medium hover:underline" href={route('operations.show', row.operation_id)}>{row.reference}</Link> : '—' }, { key: 'type', label: 'Type', render: (row) => row.type === 'entree' ? 'Entrée' : row.type === 'sortie' ? 'Sortie' : '—' }, { key: 'depot', label: 'Dépôt', render: (row) => row.depot || '—' }, { key: 'quantity', label: 'Quantité' }, { key: 'employee', label: 'Employé', render: (row) => row.employee || '—' }, { key: 'note', label: 'Note', render: (row) => row.note || '—' }, { key: 'created_at', label: 'Date', render: (row) => row.created_at || '—' }]} rows={operations.data} pagination={operations} empty="Aucune opération pour cet article." /></div>
    </AppLayout>;
}
