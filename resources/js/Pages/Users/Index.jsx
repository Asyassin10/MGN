import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';

const moduleLabels = {
    dashboard: 'Dashboard',
    depots: 'Dépôt',
    articles: 'Articles',
    operations: 'Opérations',
    bons_commande: 'Bons de commande',
    devis: 'Devis',
    groupes: 'Groupes',
    fournisseurs: 'Fournisseurs',
    clients: 'Clients',
    cheques: 'Chèques',
    caisse: 'Caisse',
};
const modules = Object.keys(moduleLabels);
const stockModules = ['depots', 'articles', 'operations', 'bons_commande', 'devis', 'groupes'];

const moduleField = (module, section) => ({ name: `module_${module}`, label: `Accès ${moduleLabels[module]}`, type: 'checkbox', ...(section ? { section } : {}) });

const fields = [
    { name: 'name', label: 'Nom complet' },
    { name: 'pin', label: 'PIN (6 chiffres)', type: 'password' },
    moduleField('dashboard', 'Général'),
    ...stockModules.map((module, index) => moduleField(module, index === 0 ? 'Dépôt (المخزون) : pages autorisées' : undefined)),
    ...['fournisseurs', 'clients', 'cheques', 'caisse'].map((module, index) => moduleField(module, index === 0 ? 'Fournisseurs, clients et finances' : undefined)),
];

export default function Index({ users }) {
    const toDefaults = (user = {}) => ({
        ...user,
        pin: '',
        ...Object.fromEntries(modules.map((module) => [`module_${module}`, user.modules?.includes(module)])),
    });

    return (
        <AppLayout title="Utilisateurs et permissions" actions={<CrudDialog title="Nouvel utilisateur" action={route('users.store')} fields={fields} defaults={toDefaults()} wide trigger={<Button>Nouvel utilisateur</Button>} />}>
            <DataTable
                columns={[
                    { key: 'name', label: 'Nom complet' },
                    { key: 'role', label: 'Profil', render: () => 'Accès limité' },
                    { key: 'modules', label: 'Accès autorisés', render: (row) => row.modules.map((module) => moduleLabels[module] || module).join(', ') || 'Aucun' },
                    { key: 'actions', label: 'Actions', render: (row) => <div className="flex gap-2"><CrudDialog title="Modifier utilisateur" action={route('users.update', row.id)} method="patch" fields={fields} defaults={toDefaults(row)} wide trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('users.destroy', row.id)} title="Supprimer cet utilisateur ?" /></div> },
                ]}
                rows={users}
                pagination={{ links: [] }}
            />
        </AppLayout>
    );
}
