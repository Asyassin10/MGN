import * as Popover from '@radix-ui/react-popover';
import { Link, router } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Download, FileText, Plus } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import ExportableDataTable from '@/Components/ExportableDataTable';
import DeleteButton from '@/Components/DeleteButton';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { money } from '@/lib/utils';
import InvoiceReceiptSelect from '@/Components/InvoiceReceiptSelect';
import SearchableSelect from '@/Components/SearchableSelect';
import { getChequeRowClass } from '@/lib/chequeStatus';

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
                        <label className="grid gap-1 text-base">
                            <span className="font-medium text-zinc-700">Du</span>
                            <Input type="date" defaultValue={from || ''} onChange={(event) => onChange({ from: event.target.value, to })} />
                        </label>
                        <label className="grid gap-1 text-base">
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
        { name: 'type', label: 'Type', type: 'select', options: [{ value: 'cheque', label: 'Chèque' }, { value: 'effet', label: 'Effet' }] },
        { name: 'numero_cheque', label: 'N chèque' },
        { name: 'banque', label: 'Banque' },
        { name: 'tireur_signataire', label: 'Tireur / signataire' },
        { name: 'date_emission', label: 'Émission', type: 'date' },
        { name: 'date_echeance', label: 'Échéance', type: 'date' },
        { name: 'statut', label: 'Statut', type: 'select', options: [{ value: 'en_cours', label: 'En cours' }, { value: 'en_caisse', label: 'En caisse' }, { value: 'impaye', label: 'Impayé' }] },
        { name: 'facture_recue', label: 'Facture reçue', type: 'checkbox' },
        { name: 'facture_donnee', label: 'Facture donnée', type: 'checkbox' },
        { name: 'montant', label: 'Montant DH', type: 'number' },
        { name: 'note', label: 'Note', type: 'textarea' },
    ];

    return (
        <AppLayout
            title={`Relevé ${releve.code_client}`}
            actions={<><Link href={route('fournisseurs.show', fournisseur.id)}><Button variant="outline"><ArrowLeft className="h-4 w-4" />Liste des relevés</Button></Link><a href={route('fournisseurs.releves.pdf', [fournisseur.id, releve.id])} target="_blank" rel="noopener noreferrer"><Button variant="outline"><FileText className="h-4 w-4" />Voir PDF relevé</Button></a><CrudDialog title="Modifier relevé compte" action={route('fournisseurs.releves.update', [fournisseur.id, releve.id])} method="patch" fields={releveFields} defaults={releve} trigger={<Button variant="outline">Modifier relevé</Button>} /><DeleteButton action={route('fournisseurs.releves.destroy', [fournisseur.id, releve.id])} title={`Supprimer le relevé ${releve.code_client} ?`} message="La suppression sera refusée tant que ce relevé contient des factures ou paiements." /></>}
        >
            <div className="mb-4 text-base text-zinc-600">
                Fournisseur : <Link className="font-medium text-zinc-950 hover:underline" href={route('fournisseurs.show', fournisseur.id)}>{fournisseur.nom}</Link>
            </div>
            <div className="mb-4 grid gap-4 md:grid-cols-5">
                <Card><CardContent><div className="text-sm uppercase text-zinc-500">Code client</div><div className="mt-2 font-medium">{releve.code_client}</div></CardContent></Card>
                <Card><CardContent><div className="text-sm uppercase text-zinc-500">Date relevé</div><div className="mt-2 font-medium">{releve.date_releve}</div></CardContent></Card>
                <Card><CardContent><div className="text-sm uppercase text-zinc-500">Factures reçues ({releve.factures_count})</div><div className="mt-2 font-medium text-red-700">{money(releve.total_factures)}</div></CardContent></Card>
                <Card><CardContent><div className="text-sm uppercase text-zinc-500">Paiements donnés ({releve.payments_count})</div><div className="mt-2 font-medium text-emerald-700">{money(releve.total_paye)}</div></CardContent></Card>
                <Card><CardContent><div className="text-sm uppercase text-zinc-500">Reste</div><div className="mt-2 font-medium">{money(releve.balance)}</div></CardContent></Card>
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
                    <ExportableDataTable
                        columns={[
                            { key: 'date_facture', label: 'Date facture' },
                            { key: 'numero_facture', label: 'N facture' },
                            { key: 'montant', label: 'Montant DH', render: (row) => money(row.montant) },
                            { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-wrap gap-2"><CrudDialog title="Modifier facture" action={route('fournisseurs.releves.factures.update', [fournisseur.id, releve.id, row.id])} method="patch" fields={factureFields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('fournisseurs.releves.factures.destroy', [fournisseur.id, releve.id, row.id])} title="Supprimer cette facture ?" /></div> },
                        ]}
                        rows={factures.data}
                        pagination={factures}
                        exportUrl={route('fournisseurs.releves.show', [fournisseur.id, releve.id])}
                        exportParams={{ ...filters, export: 'factures' }}
                    />
                </TabsContent>

                <TabsContent value="payments">
                    <div className="mb-3 grid gap-2 xl:grid-cols-[1fr_1fr_180px_280px_170px_190px]">
                        <Input placeholder="N chèque" defaultValue={filters.payment_cheque || ''} onChange={(event) => update({ payment_cheque: event.target.value })} />
                        <Input placeholder="Banque" defaultValue={filters.payment_banque || ''} onChange={(event) => update({ payment_banque: event.target.value })} />
                        <SearchableSelect value={filters.payment_statut || ''} onChange={(value) => update({ payment_statut: value })} options={[{ value: 'en_cours', label: 'En cours' }, { value: 'en_caisse', label: 'En caisse' }, { value: 'impaye', label: 'Impayé' }]} placeholder="Tous statuts" />
                        <DateRangeFilter
                            label="Période paiement"
                            from={filters.payment_date_from || ''}
                            to={filters.payment_date_to || ''}
                            onChange={({ from, to }) => update({ payment_date_from: from, payment_date_to: to })}
                        />
                        <a href={route('fournisseurs.releves.show', { fournisseur: fournisseur.id, releve: releve.id, ...filters, export: 'payments' })}><Button variant="outline"><Download className="h-4 w-4" />Export Excel</Button></a>
                        <CrudDialog title="Ajouter chèque / effet" action={route('fournisseurs.releves.payments.store', [fournisseur.id, releve.id])} fields={paymentFields} defaults={{ type: 'cheque', numero_cheque: '', banque: '', tireur_signataire: '', date_emission: '', date_echeance: '', statut: 'en_cours', facture_recue: false, facture_donnee: false, montant: '', note: '' }} trigger={<Button><Plus className="h-4 w-4" />Ajouter chèque</Button>} />
                    </div>
                    <ExportableDataTable
                        columns={[
                            { key: 'numero_cheque', label: 'N chèque' },
                            { key: 'type', label: 'Type' },
                            { key: 'fournisseur', label: 'Fournisseur', render: () => fournisseur.nom },
                            { key: 'banque', label: 'Banque' },
                            { key: 'tireur_signataire', label: 'Tireur / signataire' },
                            { key: 'montant', label: 'Montant DH', render: (row) => money(row.montant) },
                            { key: 'date_emission', label: 'Émission' },
                            { key: 'date_echeance', label: 'Échéance' },
                            { key: 'statut', label: 'Statut', render: (row) => <SearchableSelect value={row.statut} onChange={(value) => router.patch(route('fournisseurs.releves.cheques.status', [fournisseur.id, releve.id, row.id]), { statut: value }, { preserveScroll: true })} options={[{ value: 'en_cours', label: 'En cours' }, { value: 'en_caisse', label: 'En caisse' }, { value: 'impaye', label: 'Impayé' }]} allowEmpty={false} /> },
                            { key: 'facture_recue', label: 'Facture reçue', render: (row) => <InvoiceReceiptSelect value={row.facture_recue} onChange={(value) => router.patch(route('fournisseurs.releves.cheques.status', [fournisseur.id, releve.id, row.id]), { facture_recue: value }, { preserveScroll: true })} /> },
                            { key: 'facture_donnee', label: 'Facture donnée', render: (row) => <InvoiceReceiptSelect value={row.facture_donnee} onChange={(value) => router.patch(route('fournisseurs.releves.cheques.status', [fournisseur.id, releve.id, row.id]), { facture_donnee: value }, { preserveScroll: true })} /> },
                            { key: 'actions', label: 'Actions', render: (row) => <div className="flex flex-wrap gap-2"><a href={route('fournisseurs.releves.payments.pdf', [fournisseur.id, releve.id, row.id])} target="_blank" rel="noopener noreferrer"><Button size="sm" variant="outline"><FileText className="h-4 w-4" />Voir PDF</Button></a><CrudDialog title="Modifier paiement" action={route('fournisseurs.releves.payments.update', [fournisseur.id, releve.id, row.id])} method="patch" fields={paymentFields} defaults={row} trigger={<Button size="sm" variant="outline">Modifier</Button>} /><DeleteButton action={route('fournisseurs.releves.payments.destroy', [fournisseur.id, releve.id, row.id])} title="Supprimer ce paiement ?" /></div> },
                        ]}
                        rows={payments.data}
                        pagination={payments}
                        exportUrl={route('fournisseurs.releves.show', [fournisseur.id, releve.id])}
                        exportParams={{ ...filters, export: 'payments' }}
                        rowClassName={getChequeRowClass}
                    />
                </TabsContent>
            </Tabs>
        </AppLayout>
    );
}
