import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';

const modules = ['dashboard', 'depots', 'fournisseurs', 'clients', 'employees', 'cheques'];
const moduleLabels = { dashboard: 'Dashboard', depots: 'Dépôt', fournisseurs: 'Fournisseurs', clients: 'Clients', employees: 'RH / Employés', cheques: 'Chèques' };
const fields = [{ name: 'name', label: 'Nom complet' }, { name: 'pin', label: 'PIN (6 chiffres)', type: 'password' }, ...modules.map((module) => ({ name: `module_${module}`, label: `Accès ${moduleLabels[module]}`, type: 'checkbox' })), ...modules.filter((module) => module !== 'dashboard').map((module) => ({ name: `delete_${module}`, label: `Autoriser suppression ${moduleLabels[module]}`, type: 'checkbox' }))];
export default function Index({ users }) {
    const toDefaults = (user = {}) => ({
        ...user,
        pin: '',
        ...Object.fromEntries(modules.map((module) => [`module_${module}`, user.modules?.includes(module)])),
        ...Object.fromEntries(modules.filter((module) => module !== 'dashboard').map((module) => [`delete_${module}`, user.delete_modules?.includes(module)])),
    });
    const normalize = (items) => items.map((field) => field.name.startsWith('module_') ? { ...field, name: field.name } : field);
    return <AppLayout title="Utilisateurs et permissions" actions={<CrudDialog title="Nouvel utilisateur" action={route('users.store')} fields={normalize(fields)} defaults={toDefaults()} trigger={<Button>Nouvel utilisateur</Button>} />}><DataTable columns={[{ key: 'name', label: 'Nom complet' }, { key: 'role', label: 'Profil', render: () => 'Accès limité' }, { key: 'modules', label: 'Accès autorisés', render: (row) => row.modules.map((module) => moduleLabels[module]).join(', ') || 'Aucun' }, { key: 'delete_modules', label: 'Suppressions autorisées', render: (row) => row.delete_modules.map((module) => moduleLabels[module]).join(', ') || 'Aucune' }, { key: 'actions', label: 'Actions', render: (row) => <div className="flex gap-2"><CrudDialog title="Modifier utilisateur" action={route('users.update', row.id)} method="patch" fields={fields} defaults={toDefaults(row)} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('users.destroy', row.id)} title="Supprimer cet utilisateur ?" /></div> }]} rows={users} pagination={{ links: [] }} /></AppLayout>;
}
