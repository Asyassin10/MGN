import { router } from '@inertiajs/react';
import { Check, Plus, X } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import PrintPdfButton from '@/Components/PrintPdfButton';
import SearchableSelect from '@/Components/SearchableSelect';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Link } from '@inertiajs/react';

const statuses = [{ value: 'en_attente', label: 'En attente' }, { value: 'valide', label: 'Validé' }, { value: 'annule', label: 'Annulé' }];
const statusBadge = { en_attente: ['yellow', 'En attente'], valide: ['green', 'Validé'], annule: ['red', 'Annulé'] };

export default function Index({ devis, filters, fournisseurs, depots }) {
    const [confirming, setConfirming] = useState(null);
    const [depotId, setDepotId] = useState('');
    const update = (key, value) => router.get(route('devis.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const validate = () => {
        if (!depotId) return;
        router.patch(route('devis.validate', confirming.id), { depot_id: depotId }, {
            preserveScroll: true,
            onFinish: () => setConfirming(null),
        });
    };

    const columns = [
        { key: 'created_at', label: 'Date / heure' },
        { key: 'reference', label: 'Référence devis' },
        { key: 'fournisseur', label: 'Fournisseur' },
        { key: 'depot', label: 'Dépôt', render: (row) => row.depot || '—' },
        { key: 'articles', label: 'Articles / quantités', render: (row) => <span className="block max-w-md text-sm">{row.articles}</span> },
        { key: 'status', label: 'Statut', render: (row) => <Badge variant={statusBadge[row.status]?.[0]}>{statusBadge[row.status]?.[1] || row.status}</Badge> },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    {row.status === 'en_attente' ? <>
                        <Button size="sm" className="bg-emerald-600 text-white hover:bg-emerald-700" title="Valider" onClick={() => { setDepotId(''); setConfirming(row); }}><Check className="h-4 w-4" />Valider</Button>
                        <Button size="sm" variant="destructive" title="Annuler" onClick={() => router.patch(route('devis.cancel', row.id), {}, { preserveScroll: true })}><X className="h-4 w-4" />Annuler</Button>
                    </> : null}
                    <PrintPdfButton url={row.pdf_url} size="sm" />
                    {row.status !== 'valide' ? <DeleteButton action={route('devis.destroy', row.id)} title={`Supprimer ${row.reference} ?`} /> : null}
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Devis" actions={<Link href={route('devis.create')}><Button><Plus className="h-4 w-4" />Nouveau devis</Button></Link>}>
            <div className="mb-4 grid gap-2 md:grid-cols-3">
                <Input placeholder="Référence ou fournisseur" defaultValue={filters.search || ''} onChange={(event) => update('search', event.target.value)} />
                <SearchableSelect value={filters.fournisseur_id || ''} onChange={(value) => update('fournisseur_id', value)} options={fournisseurs} placeholder="Tous les fournisseurs" />
                <SearchableSelect value={filters.status || ''} onChange={(value) => update('status', value)} options={statuses} placeholder="Tous les statuts" />
            </div>
            <DataTable columns={columns} rows={devis.data} pagination={devis} empty="Aucun devis." />

            <Dialog open={Boolean(confirming)} onOpenChange={(open) => !open && setConfirming(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>Valider le devis {confirming?.reference} ?</DialogTitle></DialogHeader>
                    <p className="mb-2 text-base text-zinc-600">Êtes-vous sûr ? Choisissez le dépôt : les quantités suivantes seront ajoutées automatiquement à son stock.</p>
                    <p className="mb-3 rounded-md bg-zinc-50 p-2 text-sm text-zinc-800">{confirming?.articles}</p>
                    <label className="mb-5 grid gap-1 text-base"><span className="font-medium text-zinc-700">Depot (المستودع)</span><SearchableSelect value={depotId} onChange={setDepotId} options={depots} placeholder="Choisir le dépôt" allowEmpty={false} /></label>
                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" onClick={() => setConfirming(null)}>Annuler</Button>
                        <Button type="button" className="bg-emerald-600 text-white hover:bg-emerald-700" disabled={!depotId} onClick={validate}>Confirmer la validation</Button>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
