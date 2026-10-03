import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CreatedAtFilter from '@/Components/CreatedAtFilter';
import CaisseMovementDialog from '@/Components/CaisseMovementDialog';
import ExportableDataTable from '@/Components/ExportableDataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { money } from '@/lib/utils';

const modeLabels = { espece: 'Espèce', virement: 'Virement', cheque: 'Chèque', effet: 'Effet' };

export default function Index({ entries, parties, kpis, newParty, filters = {} }) {
    const { auth } = usePage().props;
    const isAdmin = auth.user?.role === 'admin' || !auth.user?.role;
    const defaults = { type: 'entree', party: '', montant: '', mode: '', note: '' };

    const columns = [
        { key: 'created_at', label: 'Date' },
        { key: 'type', label: 'Type', render: (row) => <Badge className="text-base" variant={row.type === 'entree' ? 'green' : 'red'}>{row.type === 'entree' ? 'Entrée' : 'Sortie'}</Badge> },
        { key: 'party_label', label: 'Client / Fournisseur', render: (row) => <span className="text-lg font-semibold">{row.party_label}</span> },
        { key: 'montant', label: 'Montant', render: (row) => <span className="text-lg font-semibold">{money(row.montant)}</span> },
        { key: 'mode', label: 'Mode', render: (row) => modeLabels[row.mode] || '—' },
        { key: 'note', label: 'Note' },
        { key: 'created_by', label: 'Créé par', render: (row) => row.created_by || '—' },
        ...(isAdmin ? [{ key: 'status', label: 'Statut', render: (row) => <Badge variant={row.validated ? 'green' : 'yellow'}>{row.validated ? 'Validé' : 'En attente'}</Badge> }] : []),
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    {!row.validated && isAdmin ? (
                        <Button size="sm" onClick={() => router.patch(route('caisse.validate', row.id), {}, { preserveScroll: true })}>Valider</Button>
                    ) : null}
                    <CaisseMovementDialog title="Modifier le mouvement" action={route('caisse.update', row.id)} method="patch" defaults={row} parties={parties} newParty={newParty} trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                    <DeleteButton action={route('caisse.destroy', row.id)} title="Supprimer ce mouvement ?" />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Caisse" actions={<CaisseMovementDialog title="Nouveau mouvement de caisse" action={route('caisse.store')} defaults={defaults} parties={parties} newParty={newParty} trigger={<Button>Nouveau mouvement</Button>} />}>
            {kpis ? (
                <div className="mb-4 grid gap-3 md:grid-cols-3">
                    <Card><CardContent><div className="text-sm text-zinc-500">Total entrées</div><div className="mt-1 text-2xl font-semibold text-emerald-700">{money(kpis.total_entree)}</div></CardContent></Card>
                    <Card><CardContent><div className="text-sm text-zinc-500">Total sorties</div><div className="mt-1 text-2xl font-semibold text-red-700">{money(kpis.total_sortie)}</div></CardContent></Card>
                    <Card><CardContent><div className="text-sm text-zinc-500">Solde (entrées - sorties)</div><div className={`mt-1 text-2xl font-semibold ${kpis.solde >= 0 ? 'text-emerald-700' : 'text-red-700'}`}>{money(kpis.solde)}</div></CardContent></Card>
                </div>
            ) : null}
            <div className="mb-4 max-w-md"><CreatedAtFilter routeName="caisse.index" filters={filters} /></div>
            {isAdmin ? (
                <div className="mb-3 flex flex-wrap items-center gap-2 text-sm font-medium">
                    <span className="rounded-md bg-[#00ff00] px-3 py-1 text-[#003300]">Entrée validée</span>
                    <span className="rounded-md bg-[#f87171] px-3 py-1 text-[#450a0a]">Entrée en attente</span>
                    <span className="rounded-md bg-[#dc2626] px-3 py-1 text-white">Sortie validée</span>
                    <span className="rounded-md bg-[#facc15] px-3 py-1 text-[#422006]">Sortie en attente</span>
                </div>
            ) : null}
            <ExportableDataTable
                columns={columns}
                rows={entries.data}
                pagination={entries}
                empty="Aucun mouvement de caisse."
                exportUrl={route('caisse.index')}
                exportParams={{ ...filters, export: 1 }}
                deleteUrl={route('caisse.destroy-selected')}
                preserveSelection
                totalLabel="Total (entrées − sorties)"
                totalValue={(row) => (row.type === 'sortie' ? -row.montant : row.montant)}
                summaryExtra={(rows) => `Entrées : ${money(rows.filter((row) => row.type === 'entree').reduce((sum, row) => sum + row.montant, 0))} · Sorties : ${money(rows.filter((row) => row.type === 'sortie').reduce((sum, row) => sum + row.montant, 0))}`}
                rowClassName={(row) => (isAdmin ? 'status-row status-row-caisse-' + row.type + '-' + (row.validated ? 'valide' : 'attente') : '')}
            />
        </AppLayout>
    );
}
