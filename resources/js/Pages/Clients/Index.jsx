import { Link, router } from '@inertiajs/react';
import { Download, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { money } from '@/lib/utils';

export default function Index({ clients, filters }) {
    const update = (key, value) => router.get(route('clients.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const fields = [{ name: 'nom', label: 'Nom' }, { name: 'telephone', label: 'Téléphone' }, { name: 'ville', label: 'Ville' }, { name: 'note', label: 'Note', type: 'textarea' }];
    const columns = [
        { key: 'nom', label: 'Nom', render: (row) => <Link className="font-medium text-zinc-950 hover:underline" href={route('clients.show', row.id)}>{row.nom}</Link> },
        { key: 'ville', label: 'Ville' },
        { key: 'telephone', label: 'Tél' },
        { key: 'total_du', label: 'Total Dû', render: (row) => money(row.total_du) },
        { key: 'total_paye', label: 'Total Payé', render: (row) => money(row.total_paye) },
        { key: 'balance', label: 'Solde', render: (row) => <span className={row.balance > 0 ? 'text-green-700' : 'text-red-700'}>{money(row.balance)}</span> },
        { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-wrap gap-2"><Link href={route('clients.show', row.id)}><Button size="sm" variant="outline">Voir</Button></Link><CrudDialog title={`Modifier ${row.nom}`} action={route('clients.update', row.id)} method="patch" fields={fields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('clients.destroy', row.id)} title={`Supprimer ${row.nom} ?`} message="La suppression sera refusée tant que ce client possède des entrées, paiements ou chèques." /></div> },
    ];
    return <AppLayout title="Clients" actions={<><a href={route('clients.index', { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><Link href={route('clients.create')}><Button><Plus className="h-4 w-4" />Nouveau</Button></Link></>}><div className="mb-4 grid gap-2 md:grid-cols-4"><Input placeholder="Recherche nom" defaultValue={filters.search || ''} onChange={(e) => update('search', e.target.value)} /><Input placeholder="Ville" defaultValue={filters.ville || ''} onChange={(e) => update('ville', e.target.value)} /><Input type="number" placeholder="Solde min" defaultValue={filters.balance_min || ''} onChange={(e) => update('balance_min', e.target.value)} /><Input type="number" placeholder="Solde max" defaultValue={filters.balance_max || ''} onChange={(e) => update('balance_max', e.target.value)} /></div><DataTable columns={columns} rows={clients.data} pagination={clients} onRowClick={(row) => router.visit(route('clients.show', row.id))} /></AppLayout>;
}
