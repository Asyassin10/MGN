import { router } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import DataTable from '@/Components/DataTable';
import { Input } from '@/Components/ui/input';

export default function Index({ logs, filters }) {
    const update = (changes) => router.get(route('activity-history.index'), { ...filters, ...changes }, { preserveState: true, replace: true });
    const details = (items) => items?.length ? <ul className="space-y-1 text-sm">{items.map((item) => <li key={item.label}><span className="font-medium">{item.label} :</span> {item.value}</li>)}</ul> : '—';
    return <AppLayout title="Historique administrateur"><div className="mb-4 grid gap-2 md:grid-cols-3"><Input placeholder="Rechercher action, module ou document" defaultValue={filters.search || ''} onChange={(event) => update({ search: event.target.value })} /><Input type="datetime-local" aria-label="Date et heure de début" defaultValue={filters.from || ''} onChange={(event) => update({ from: event.target.value })} /><Input type="datetime-local" aria-label="Date et heure de fin" defaultValue={filters.to || ''} onChange={(event) => update({ to: event.target.value })} /></div><DataTable columns={[{ key: 'created_at', label: 'Date / heure' }, { key: 'user', label: 'Utilisateur', render: (row) => row.user?.name || '—' }, { key: 'action', label: 'Action' }, { key: 'module', label: 'Module' }, { key: 'subject_label', label: 'Document' }, { key: 'open', label: 'Ouvrir', render: (row) => row.url ? <a href={row.url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-sm font-medium text-zinc-900 underline underline-offset-4"><ExternalLink className="h-3.5 w-3.5" />Voir</a> : '—' }, { key: 'before', label: 'Avant', render: (row) => details(row.before) }, { key: 'after', label: 'Après', render: (row) => details(row.after) }]} rows={logs.data} pagination={logs} empty="Aucune activité d’utilisateur à accès limité." /></AppLayout>;
}
