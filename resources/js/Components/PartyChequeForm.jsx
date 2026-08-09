import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import InvoiceReceiptSelect from '@/Components/InvoiceReceiptSelect';

const types = [{ value: 'cheque', label: 'Chèque' }, { value: 'effet', label: 'Effet' }];
const statuses = [{ value: 'en_cours', label: 'En cours' }, { value: 'en_caisse', label: 'En caisse' }, { value: 'impaye', label: 'Impayé' }];

export default function PartyChequeForm({ title, action, method = 'post', cheque = {}, ownerKey, ownerLabel, owners, banks = [] }) {
    const { data, setData, post, patch, processing, errors } = useForm({
        type: cheque.type || 'cheque',
        [ownerKey]: cheque[ownerKey] || '',
        bank_id: cheque.bank_id || '',
        numero_cheque: cheque.numero_cheque || '',
        banque: cheque.banque || '',
        tireur_signataire: cheque.tireur_signataire || '',
        montant: cheque.montant || '',
        date_emission: cheque.date_emission || '',
        date_echeance: cheque.date_echeance || '',
        statut: cheque.statut || 'en_cours',
        facture_recue: cheque.facture_recue ?? null,
        facture_donnee: cheque.facture_donnee ?? null,
        motif: cheque.motif || '',
        note: cheque.note || '',
    });

    const submit = (event) => {
        event.preventDefault();
        (method === 'patch' ? patch : post)(action);
    };

    return (
        <AppLayout title={title}>
            <Card className="max-w-3xl">
                <CardContent>
                    <form onSubmit={submit} className="grid gap-3">
                        <div className="grid gap-3 md:grid-cols-2">
                            <SearchableSelect value={data.type} onChange={(value) => setData('type', value)} options={types} allowEmpty={false} placeholder="Type" />
                            <SearchableSelect value={data[ownerKey]} onChange={(value) => setData(ownerKey, value)} options={owners} allowEmpty={false} placeholder={ownerLabel} />
                            <label className="grid gap-1 text-base"><span className="font-medium">Numéro</span><Input value={data.numero_cheque} onChange={(e) => setData('numero_cheque', e.target.value)} />{errors.numero_cheque && <span className="text-sm text-red-600">{errors.numero_cheque}</span>}</label>
                            <SearchableSelect value={data.bank_id} onChange={(value) => setData('bank_id', value)} options={banks} placeholder="Banque" />
                            <label className="grid gap-1 text-base"><span className="font-medium">Banque manuelle</span><Input value={data.banque || ''} onChange={(e) => setData('banque', e.target.value)} placeholder="Optionnel si banque choisie" />{errors.banque && <span className="text-sm text-red-600">{errors.banque}</span>}</label>
                            <label className="grid gap-1 text-base"><span className="font-medium">Tireur / signataire</span><Input value={data.tireur_signataire || ''} onChange={(e) => setData('tireur_signataire', e.target.value)} /></label>
                            <label className="grid gap-1 text-base"><span className="font-medium">Montant</span><Input type="number" step="0.01" value={data.montant} onChange={(e) => setData('montant', e.target.value)} />{errors.montant && <span className="text-sm text-red-600">{errors.montant}</span>}</label>
                            <label className="grid gap-1 text-base"><span className="font-medium">Émission</span><Input type="date" value={data.date_emission || ''} onChange={(e) => setData('date_emission', e.target.value)} /></label>
                            <label className="grid gap-1 text-base"><span className="font-medium">Échéance</span><Input type="date" value={data.date_echeance || ''} onChange={(e) => setData('date_echeance', e.target.value)} /></label>
                            <SearchableSelect value={data.statut} onChange={(value) => setData('statut', value)} options={statuses} allowEmpty={false} placeholder="Statut" />
                            <label className="grid gap-1 text-base"><span className="font-medium">Facture reçue</span><InvoiceReceiptSelect value={data.facture_recue} onChange={(value) => setData('facture_recue', value)} label="Facture reçue" /></label>
                            <label className="grid gap-1 text-base"><span className="font-medium">Facture donnée</span><InvoiceReceiptSelect value={data.facture_donnee} onChange={(value) => setData('facture_donnee', value)} label="Facture donnée" /></label>
                        </div>
                        <label className="grid gap-1 text-base"><span className="font-medium">Motif</span><Textarea value={data.motif || ''} onChange={(e) => setData('motif', e.target.value)} /></label>
                        <label className="grid gap-1 text-base"><span className="font-medium">Note</span><Textarea value={data.note || ''} onChange={(e) => setData('note', e.target.value)} /></label>
                        <div><Button disabled={processing}>Enregistrer</Button></div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
