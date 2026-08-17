import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import DataTable from '@/Components/DataTable';
import { Input } from '@/Components/ui/input';

export default function Index({ logs, filters }) {
    return <AppLayout title="Historique administrateur"><div className="mb-4"><Input placeholder="Rechercher action, module ou document" defaultValue={filters.search || ''} onChange={(event) => router.get(route('activity-history.index'), { search: event.target.value }, { preserveState: true, replace: true })} /></div><DataTable columns={[{ key: 'created_at', label: 'Date / heure' }, { key: 'user', label: 'Utilisateur', render: (row) => row.user?.name || 'Système' }, { key: 'action', label: 'Action' }, { key: 'module', label: 'Module' }, { key: 'subject_label', label: 'Document' }, { key: 'before', label: 'Avant', render: (row) => row.before ? JSON.stringify(row.before) : '-' }, { key: 'after', label: 'Après', render: (row) => row.after ? JSON.stringify(row.after) : '-' }]} rows={logs.data} pagination={logs} /></AppLayout>;
}
