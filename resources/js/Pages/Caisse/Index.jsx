import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { money } from '@/lib/utils';

const types = [{ value: 'entree', label: 'Entrée' }, { value: 'sortie', label: 'Sortie' }];

export default function Index({ entries, parties, kpis }) {
    const fields = [
        { name: 'type', label: 'Type', type: 'select', options: types, allowEmpty: false },
        { name: 'party', label: 'Client / Fournisseur', type: 'select', options: parties, allowEmpty: false },
        { name: 'montant', label: 'Montant', type: 'number' },
        { name: 'note', label: 'Note', type: 'textarea' },
    ];
    const defaults = { type: 'entree', party: '', montant: '', note: '' };

    const columns = [
        { key: 'created_at', label: 'Date' },
        { key: 'type', label: 'Type', render: (row) => <Badge className="text-base" variant={row.type === 'entree' ? 'green' : 'red'}>{row.type === 'entree' ? 'Entrée' : 'Sortie'}</Badge> },
        { key: 'party_label', label: 'Client / Fournisseur', render: (row) => <span className="text-lg font-semibold">{row.party_label}</span> },
        { key: 'montant', label: 'Montant', render: (row) => <span className={`text-lg font-semibold ${row.type === 'entree' ? 'text-emerald-700' : 'text-red-700'}`}>{money(row.montant)}</span> },
        { key: 'note', label: 'Note' },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <CrudDialog title="Modifier le mouvement" action={route('caisse.update', row.id)} method="patch" fields={fields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} />
                    <DeleteButton action={route('caisse.destroy', row.id)} title="Supprimer ce mouvement ?" />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Caisse" actions={<CrudDialog title="Nouveau mouvement de caisse" action={route('caisse.store')} fields={fields} defaults={defaults} trigger={<Button>Nouveau mouvement</Button>} />}>
            {kpis ? (
                <div className="mb-4 grid gap-3 md:grid-cols-3">
                    <Card><CardContent><div className="text-sm text-zinc-500">Total entrées</div><div className="mt-1 text-2xl font-semibold text-emerald-700">{money(kpis.total_entree)}</div></CardContent></Card>
                    <Card><CardContent><div className="text-sm text-zinc-500">Total sorties</div><div className="mt-1 text-2xl font-semibold text-red-700">{money(kpis.total_sortie)}</div></CardContent></Card>
                    <Card><CardContent><div className="text-sm text-zinc-500">Solde (entrées - sorties)</div><div className={`mt-1 text-2xl font-semibold ${kpis.solde >= 0 ? 'text-emerald-700' : 'text-red-700'}`}>{money(kpis.solde)}</div></CardContent></Card>
                </div>
            ) : null}
            <DataTable columns={columns} rows={entries.data} pagination={entries} empty="Aucun mouvement de caisse." />
        </AppLayout>
    );
}
