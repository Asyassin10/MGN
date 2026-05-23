import { Link } from '@inertiajs/react';
import { Download, FileText } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';

export default function Show({ operation }) {
    const columns = [
        { key: 'reference', label: 'Référence' },
        { key: 'article_name', label: 'Article' },
        { key: 'quantity', label: 'Quantité' },
    ];

    return (
        <AppLayout
            title={operation.reference || `Opération #${operation.id}`}
            actions={
                <>
                    <a href={operation.pdf_url} target="_blank" rel="noopener noreferrer"><Button variant="outline"><FileText className="h-4 w-4" />Voir PDF</Button></a>
                    <a href={operation.excel_url}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a>
                    <Link href={route('operations.edit', operation.id)}><Button>Modifier</Button></Link>
                    <Link href={route('operations.index')}><Button variant="outline">Retour</Button></Link>
                    <DeleteButton action={route('operations.destroy', operation.id)} title={`Supprimer ${operation.reference} ?`} message="La suppression rétablira automatiquement les quantités de stock si cette opération peut encore être annulée." />
                </>
            }
        >
            <div className="mb-4 grid gap-4 md:grid-cols-5">
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Référence</div><div className="mt-2 font-medium">{operation.reference}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Date</div><div className="mt-2 font-medium">{operation.created_at}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Type</div><div className="mt-2"><Badge variant={operation.type === 'entree' ? 'green' : 'red'}>{operation.type}</Badge></div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Dépôt</div><div className="mt-2 font-medium">{operation.depot?.name || '-'}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Employé</div><div className="mt-2 font-medium">{operation.employee?.name || '-'}</div></CardContent></Card>
            </div>

            <DataTable columns={columns} rows={operation.lines} pagination={{ links: [] }} empty="Aucune ligne pour cette opération." />

            {operation.note ? (
                <Card className="mt-4">
                    <CardContent>
                        <div className="text-xs uppercase text-zinc-500">Note</div>
                        <div className="mt-2 text-sm">{operation.note}</div>
                    </CardContent>
                </Card>
            ) : null}
        </AppLayout>
    );
}
