import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, FileText, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { money } from '@/lib/utils';

export default function Show({ fournisseur, releves }) {
    const fournisseurFields = [{ name: 'nom', label: 'Nom' }, { name: 'telephone', label: 'Téléphone' }, { name: 'ville', label: 'Ville' }, { name: 'note', label: 'Note', type: 'textarea' }];
    const releveFields = [
        { name: 'code_client', label: 'Code client' },
        { name: 'date_releve', label: 'Date relevé', type: 'date' },
        { name: 'note', label: 'Note', type: 'textarea' },
    ];

    const columns = [
        {
            key: 'code_client',
            label: 'Code client',
            render: (row) => <Link className="font-medium text-zinc-950 hover:underline" href={route('fournisseurs.releves.show', [fournisseur.id, row.id])}>{row.code_client}</Link>,
        },
        { key: 'date_releve', label: 'Date relevé' },
        { key: 'total_factures', label: 'Total factures', render: (row) => money(row.total_factures) },
        { key: 'total_paye', label: 'Total paiements', render: (row) => money(row.total_paye) },
        { key: 'balance', label: 'Solde', render: (row) => <span className={row.balance > 0 ? 'text-red-700' : 'text-green-700'}>{money(row.balance)}</span> },
        { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-wrap gap-2"><Link href={route('fournisseurs.releves.show', [fournisseur.id, row.id])}><Button size="sm" variant="outline">Voir</Button></Link><a href={route('fournisseurs.releves.pdf', [fournisseur.id, row.id])} target="_blank" rel="noopener noreferrer"><Button size="sm" variant="outline"><FileText className="h-4 w-4" />Voir PDF</Button></a><CrudDialog title="Modifier relevé compte" action={route('fournisseurs.releves.update', [fournisseur.id, row.id])} method="patch" fields={releveFields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('fournisseurs.releves.destroy', [fournisseur.id, row.id])} title={`Supprimer le relevé ${row.code_client} ?`} message="La suppression sera refusée tant que ce relevé contient des factures ou paiements." /></div> },
    ];

    return (
        <AppLayout
            title={`Relevés compte - ${fournisseur.nom}`}
            actions={<><Link href={route('fournisseurs.index')}><Button variant="outline"><ArrowLeft className="h-4 w-4" />Tous les fournisseurs</Button></Link><CrudDialog title="Nouveau relevé compte" action={route('fournisseurs.releves.store', fournisseur.id)} fields={releveFields} defaults={{ code_client: '', date_releve: '', note: '' }} trigger={<Button><Plus className="h-4 w-4" />Nouveau relevé</Button>} /><a href={route('fournisseurs.show', { fournisseur: fournisseur.id, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export</Button></a><CrudDialog title={`Modifier ${fournisseur.nom}`} action={route('fournisseurs.update', fournisseur.id)} method="patch" fields={fournisseurFields} defaults={fournisseur} trigger={<Button variant="outline">Modifier fournisseur</Button>} /><DeleteButton action={route('fournisseurs.destroy', fournisseur.id)} title={`Supprimer ${fournisseur.nom} ?`} message="La suppression sera refusée tant que ce fournisseur possède un historique financier ou des chèques." /></>}
        >
            <div className="mb-4 grid gap-4 md:grid-cols-4">
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Nom</div><div className="mt-2 font-medium">{fournisseur.nom}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Ville</div><div className="mt-2 font-medium">{fournisseur.ville || '-'}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Téléphone</div><div className="mt-2 font-medium">{fournisseur.telephone || '-'}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Solde</div><div className="mt-2"><Badge variant={fournisseur.balance > 0 ? 'red' : 'green'}>{money(fournisseur.balance)}</Badge></div></CardContent></Card>
            </div>

            <div className="mb-4 rounded-md border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-600">
                Les relevés compte de ce fournisseur sont listés ci-dessous. Ouvrez un relevé uniquement pour gérer ses factures et ses paiements.
            </div>

            <DataTable
                columns={columns}
                rows={releves.data}
                pagination={releves}
                empty="Aucun relevé compte pour ce fournisseur."
                onRowClick={(row) => router.visit(route('fournisseurs.releves.show', [fournisseur.id, row.id]))}
            />
        </AppLayout>
    );
}
