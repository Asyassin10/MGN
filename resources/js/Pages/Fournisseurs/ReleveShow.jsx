import * as Popover from '@radix-ui/react-popover';
import { Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Download, FileText, Plus } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { money } from '@/lib/utils';

function DateRangeFilter({ from, to, onChange, label = 'Période' }) {
    const value = from || to ? `${from || 'Début'} - ${to || 'Fin'}` : label;

    return (
        <Popover.Root>
            <Popover.Trigger asChild>
                <Button type="button" variant="outline" className="w-full justify-start">
                    <CalendarDays className="h-4 w-4" />
                    {value}
                </Button>
            </Popover.Trigger>
            <Popover.Portal>
                <Popover.Content align="start" className="z-50 mt-2 rounded-md border border-zinc-200 bg-white p-3">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <label className="grid gap-1 text-sm">
                            <span className="font-medium text-zinc-700">Du</span>
                            <Input type="date" defaultValue={from || ''} onChange={(event) => onChange({ from: event.target.value, to })} />
                        </label>
                        <label className="grid gap-1 text-sm">
                            <span className="font-medium text-zinc-700">Au</span>
                            <Input type="date" defaultValue={to || ''} onChange={(event) => onChange({ from, to: event.target.value })} />
                        </label>
                    </div>
                    <div className="mt-3 flex justify-end">
                        <Button type="button" variant="outline" onClick={() => onChange({ from: '', to: '' })}>Effacer</Button>
                    </div>
                </Popover.Content>
            </Popover.Portal>
        </Popover.Root>
    );
}

export default function ReleveShow({ fournisseur, releve, factures, payments, filters }) {
    const [activeTab, setActiveTab] = useState(filters.tab === 'payments' ? 'payments' : 'factures');
    const update = (payload) => router.get(route('fournisseurs.releves.show', [fournisseur.id, releve.id]), { ...filters, ...payload }, { preserveState: true, replace: true });
    const changeTab = (tab) => {
        setActiveTab(tab);
        update({ tab });
    };
    const releveFields = [
        { name: 'code_client', label: 'Code client' },
        { name: 'date_releve', label: 'Date relevé', type: 'date' },
        { name: 'note', label: 'Note', type: 'textarea' },
    ];

    const factureFields = [
        { name: 'date_facture', label: 'Date facture', type: 'date' },
        { name: 'numero_facture', label: 'N facture' },
        { name: 'montant', label: 'Montant DH', type: 'number' },
    ];
    const paymentFields = [
        { name: 'date_paiement', label: 'Date paiement', type: 'date' },
        { name: 'numero_cheque', label: 'N chèque' },
        { name: 'banque', label: 'Banque' },
        { name: 'date_echeance', label: 'Échéance', type: 'date' },
        { name: 'montant', label: 'Montant DH', type: 'number' },
    ];

    return (
        <AppLayout
            title={`Relevé ${releve.code_client}`}
            actions={<><Link href={route('fournisseurs.show', fournisseur.id)}><Button variant="outline"><ArrowLeft className="h-4 w-4" />Liste des relevés</Button></Link><a href={route('fournisseurs.releves.pdf', [fournisseur.id, releve.id])} target="_blank" rel="noopener noreferrer"><Button variant="outline"><FileText className="h-4 w-4" />Voir PDF relevé</Button></a><CrudDialog title="Modifier relevé compte" action={route('fournisseurs.releves.update', [fournisseur.id, releve.id])} method="patch" fields={releveFields} defaults={releve} trigger={<Button variant="outline">Modifier relevé</Button>} /><DeleteButton action={route('fournisseurs.releves.destroy', [fournisseur.id, releve.id])} title={`Supprimer le relevé ${releve.code_client} ?`} message="La suppression sera refusée tant que ce relevé contient des factures ou paiements." /></>}
        >
            <div className="mb-4 text-sm text-zinc-600">
                Fournisseur : <Link className="font-medium text-zinc-950 hover:underline" href={route('fournisseurs.show', fournisseur.id)}>{fournisseur.nom}</Link>
            </div>
            <div className="mb-4 grid gap-4 md:grid-cols-5">
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Code client</div><div className="mt-2 font-medium">{releve.code_client}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Date relevé</div><div className="mt-2 font-medium">{releve.date_releve}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Total relevé compte</div><div className="mt-2 font-medium">{money(releve.total_factures)}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Total paiements</div><div className="mt-2 font-medium">{money(releve.total_paye)}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Reste</div><div className="mt-2 font-medium">{money(releve.balance)}</div></CardContent></Card>
            </div>

            <Tabs value={activeTab} onValueChange={changeTab}>
                <TabsList>
                    <TabsTrigger value="factures">Factures</TabsTrigger>
                    <TabsTrigger value="payments">Paiements</TabsTrigger>
                </TabsList>

                <TabsContent value="factures">
                    <div className="mb-3 grid gap-2 lg:grid-cols-[1fr_280px_170px_180px]">
                        <Input placeholder="N facture" defaultValue={filters.facture_search || ''} onChange={(event) => update({ facture_search: event.target.value })} />
                        <DateRangeFilter
                            label="Période facture"
                            from={filters.facture_date_from || ''}
                            to={filters.facture_date_to || ''}
                            onChange={({ from, to }) => update({ facture_date_from: from, facture_date_to: to })}
                        />
                        <a href={route('fournisseurs.releves.show', { fournisseur: fournisseur.id, releve: releve.id, ...filters, export: 'factures' })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a>
                        <CrudDialog title="Ajouter facture" action={route('fournisseurs.releves.factures.store', [fournisseur.id, releve.id])} fields={factureFields} defaults={{ date_facture: '', numero_facture: '', montant: '' }} trigger={<Button><Plus className="h-4 w-4" />Ajouter facture</Button>} />
                    </div>
                    <DataTable
                        columns={[
                            { key: 'date_facture', label: 'Date facture' },
                            { key: 'numero_facture', label: 'N facture' },
                            { key: 'montant', label: 'Montant DH', render: (row) => money(row.montant) },
                            { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-wrap gap-2"><CrudDialog title="Modifier facture" action={route('fournisseurs.releves.factures.update', [fournisseur.id, releve.id, row.id])} method="patch" fields={factureFields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('fournisseurs.releves.factures.destroy', [fournisseur.id, releve.id, row.id])} title="Supprimer cette facture ?" /></div> },
                        ]}
                        rows={factures.data}
                        pagination={factures}
                    />
                </TabsContent>

                <TabsContent value="payments">
                    <div className="mb-3 grid gap-2 xl:grid-cols-[1fr_1fr_280px_170px_190px]">
                        <Input placeholder="N chèque" defaultValue={filters.payment_cheque || ''} onChange={(event) => update({ payment_cheque: event.target.value })} />
                        <Input placeholder="Banque" defaultValue={filters.payment_banque || ''} onChange={(event) => update({ payment_banque: event.target.value })} />
                        <DateRangeFilter
                            label="Période paiement"
                            from={filters.payment_date_from || ''}
                            to={filters.payment_date_to || ''}
                            onChange={({ from, to }) => update({ payment_date_from: from, payment_date_to: to })}
                        />
                        <a href={route('fournisseurs.releves.show', { fournisseur: fournisseur.id, releve: releve.id, ...filters, export: 'payments' })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a>
                        <CrudDialog title="Ajouter paiement" action={route('fournisseurs.releves.payments.store', [fournisseur.id, releve.id])} fields={paymentFields} defaults={{ date_paiement: '', numero_cheque: '', banque: '', date_echeance: '', montant: '' }} trigger={<Button><Plus className="h-4 w-4" />Ajouter paiement</Button>} />
                    </div>
                    <DataTable
                        columns={[
                            { key: 'date_paiement', label: 'Date paiement' },
                            { key: 'numero_cheque', label: 'N chèque' },
                            { key: 'banque', label: 'Banque' },
                            { key: 'date_echeance', label: 'Échéance' },
                            { key: 'montant', label: 'Montant DH', render: (row) => money(row.montant) },
                            { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-wrap gap-2"><a href={route('fournisseurs.releves.payments.pdf', [fournisseur.id, releve.id, row.id])} target="_blank" rel="noopener noreferrer"><Button size="sm" variant="outline"><FileText className="h-4 w-4" />Voir PDF</Button></a><CrudDialog title="Modifier paiement" action={route('fournisseurs.releves.payments.update', [fournisseur.id, releve.id, row.id])} method="patch" fields={paymentFields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('fournisseurs.releves.payments.destroy', [fournisseur.id, releve.id, row.id])} title="Supprimer ce paiement ?" /></div> },
                        ]}
                        rows={payments.data}
                        pagination={payments}
                    />
                </TabsContent>
            </Tabs>
        </AppLayout>
    );
}
