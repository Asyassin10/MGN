import { Link, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import DateRangePicker from '@/Components/DateRangePicker';
import DataTable from '@/Components/DataTable';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import DeleteButton from '@/Components/DeleteButton';

const types = [{ value: 'entree', label: 'Entrée' }, { value: 'sortie', label: 'Sortie' }];

export default function Index({ operations, filters, depots, employees }) {
    const update = (key, value) => router.get(route('operations.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const columns = [
        { key: 'reference', label: 'Référence' },
        { key: 'created_at', label: 'Date' },
        { key: 'type', label: 'Type', render: (r) => <Badge variant={r.type === 'entree' ? 'green' : 'red'}>{r.type}</Badge> },
        { key: 'depot', label: 'Dépôt' },
        { key: 'employee', label: 'Employé' },
        { key: 'lines_count', label: 'Lignes' },
        { key: 'actions', label: 'Actions', render: (r) => <div className="flex flex-wrap gap-2"><Link href={r.show_url}><Button size="sm" variant="outline">Voir</Button></Link><Link href={route('operations.edit', r.id)}><Button size="sm" variant="outline">Modifier</Button></Link><DeleteButton action={route('operations.destroy', r.id)} title={`Supprimer ${r.reference} ?`} message="La suppression rétablira automatiquement les quantités de stock si cette opération peut encore être annulée." /></div> },
    ];

    return <AppLayout title="Opérations" actions={<><a href={route('operations.index', { ...filters, export: 1 })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><Link href={route('operations.create')}><Button>Nouvelle opération</Button></Link></>}><div className="mb-4 grid gap-2 md:grid-cols-5"><Input placeholder="Référence" defaultValue={filters.search || ''} onChange={(e) => update('search', e.target.value)} /><SearchableSelect value={filters.type || ''} onChange={(value) => update('type', value)} options={types} placeholder="Type" /><SearchableSelect value={filters.depot_id || ''} onChange={(value) => update('depot_id', value)} options={depots} placeholder="Dépôt" /><SearchableSelect value={filters.employee_id || ''} onChange={(value) => update('employee_id', value)} options={employees} placeholder="Employé" /><DateRangePicker from={filters.date_from || ''} to={filters.date_to || ''} onChange={({ from, to }) => router.get(route('operations.index'), { ...filters, date_from: from, date_to: to }, { preserveState: true, replace: true })} label="Période" /></div><DataTable columns={columns} rows={operations.data} pagination={operations} /></AppLayout>;
}
