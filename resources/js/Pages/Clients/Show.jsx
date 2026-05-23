import { router } from '@inertiajs/react';
import { Download, FileText, Plus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import BalanceCard from '@/Components/BalanceCard';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Badge } from '@/Components/ui/badge';
import { money } from '@/lib/utils';

const modes = [{ value: 'espece', label: 'Espèce' }, { value: 'cheque', label: 'Chèque' }, { value: 'virement', label: 'Virement' }];

export default function Show({ client, entries, payments, filters }) {
    const update = (key, value) => router.get(route('clients.show', client.id), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const clientFields = [{ name: 'nom', label: 'Nom' }, { name: 'telephone', label: 'Téléphone' }, { name: 'ville', label: 'Ville' }, { name: 'note', label: 'Note', type: 'textarea' }];
    const entryFields = [{ name: 'date_entree', label: 'Date', type: 'date' }, { name: 'montant', label: 'Montant', type: 'number' }, { name: 'description', label: 'Description' }];
    const paymentFields = [{ name: 'date_paiement', label: 'Date', type: 'date' }, { name: 'montant', label: 'Montant', type: 'number' }, { name: 'mode', label: 'Mode', type: 'select', options: modes }, { name: 'reference', label: 'Référence' }, { name: 'note', label: 'Note', type: 'textarea' }];

    return (
        <AppLayout title={client.nom} actions={<><CrudDialog title={`Modifier ${client.nom}`} action={route('clients.update', client.id)} method="patch" fields={clientFields} defaults={client} trigger={<Button variant="outline">Modifier</Button>} /><DeleteButton action={route('clients.destroy', client.id)} title={`Supprimer ${client.nom} ?`} message="La suppression sera refusée tant que ce client possède des entrées, paiements ou chèques." /></>}>
            <div className="mb-4 grid gap-4 md:grid-cols-4">
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Nom</div><div className="mt-2 font-medium">{client.nom}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Ville</div><div className="mt-2 font-medium">{client.ville || '-'}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Téléphone</div><div className="mt-2 font-medium">{client.telephone || '-'}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Solde</div><div className="mt-2"><Badge variant={client.balance > 0 ? 'green' : 'red'}>{money(client.balance)}</Badge></div></CardContent></Card>
            </div>
            <Tabs defaultValue="entries">
                <TabsList><TabsTrigger value="entries">Entrées</TabsTrigger><TabsTrigger value="payments">Paiements</TabsTrigger></TabsList>
                <TabsContent value="entries">
                    <div className="mb-3 grid gap-2 md:grid-cols-6"><Input type="date" defaultValue={filters.entry_date_from || ''} onChange={(e) => update('entry_date_from', e.target.value)} /><Input type="date" defaultValue={filters.entry_date_to || ''} onChange={(e) => update('entry_date_to', e.target.value)} /><Input type="number" placeholder="Montant min" defaultValue={filters.entry_min || ''} onChange={(e) => update('entry_min', e.target.value)} /><Input type="number" placeholder="Montant max" defaultValue={filters.entry_max || ''} onChange={(e) => update('entry_max', e.target.value)} /><a href={route('clients.show', { client: client.id, ...filters, export: 'entries' })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title="Ajouter entrée" action={route('clients.entries.store', client.id)} fields={entryFields} defaults={{ date_entree: '', montant: '', description: '' }} trigger={<Button><Plus className="h-4 w-4" />Ajouter</Button>} /></div>
                    <DataTable columns={[{ key: 'date_entree', label: 'Date' }, { key: 'montant', label: 'Montant', render: (r) => money(r.montant) }, { key: 'description', label: 'Description' }, { key: 'actions', label: 'Actions', render: (r) => <div className="flex flex-wrap gap-2"><CrudDialog title="Modifier entrée" action={route('clients.entries.update', [client.id, r.id])} method="patch" fields={entryFields} defaults={r} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('clients.entries.destroy', [client.id, r.id])} title="Supprimer cette entrée ?" /></div> }]} rows={entries.data} pagination={entries} />
                </TabsContent>
                <TabsContent value="payments">
                    <div className="mb-3 grid gap-2 md:grid-cols-7"><Input type="date" defaultValue={filters.payment_date_from || ''} onChange={(e) => update('payment_date_from', e.target.value)} /><Input type="date" defaultValue={filters.payment_date_to || ''} onChange={(e) => update('payment_date_to', e.target.value)} /><SearchableSelect value={filters.payment_mode || ''} onChange={(value) => update('payment_mode', value)} options={modes} placeholder="Mode" /><Input type="number" placeholder="Montant min" defaultValue={filters.payment_min || ''} onChange={(e) => update('payment_min', e.target.value)} /><Input type="number" placeholder="Montant max" defaultValue={filters.payment_max || ''} onChange={(e) => update('payment_max', e.target.value)} /><a href={route('clients.show', { client: client.id, ...filters, export: 'payments' })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a><CrudDialog title="Ajouter paiement" action={route('clients.payments.store', client.id)} fields={paymentFields} defaults={{ date_paiement: '', montant: '', mode: 'espece', reference: '', note: '' }} trigger={<Button><Plus className="h-4 w-4" />Ajouter</Button>} /></div>
                    <DataTable columns={[{ key: 'date_paiement', label: 'Date' }, { key: 'montant', label: 'Montant', render: (r) => money(r.montant) }, { key: 'mode', label: 'Mode' }, { key: 'reference', label: 'Référence' }, { key: 'actions', label: 'Actions', render: (r) => <div className="flex flex-wrap gap-2"><a href={route('clients.payments.pdf', [client.id, r.id])}><Button size="sm" variant="outline"><FileText className="h-4 w-4" />PDF</Button></a><CrudDialog title="Modifier paiement" action={route('clients.payments.update', [client.id, r.id])} method="patch" fields={paymentFields} defaults={r} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('clients.payments.destroy', [client.id, r.id])} title="Supprimer ce paiement ?" /></div> }]} rows={payments.data} pagination={payments} />
                </TabsContent>
            </Tabs>
        </AppLayout>
    );
}
