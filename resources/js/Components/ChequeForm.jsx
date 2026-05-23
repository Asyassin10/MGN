import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';

const types = [{ value: 'client', label: 'Client' }, { value: 'fournisseur', label: 'Fournisseur' }];
const statuses = [{ value: 'en_cours', label: 'En cours' }, { value: 'encaisse', label: 'Encaissé' }, { value: 'impaye', label: 'Impayé' }];

export default function ChequeForm({ title, action, method = 'post', cheque = {}, tiers, banques }) {
    const tierOptions = [...(tiers.clients || []), ...(tiers.fournisseurs || [])];
    const { data, setData, post, patch, processing, errors } = useForm({
        type: cheque.type || 'client',
        tier_value: cheque.tier_value || '',
        numero_cheque: cheque.numero_cheque || '',
        banque: cheque.banque || '',
        tireur_signataire: cheque.tireur_signataire || '',
        montant: cheque.montant || '',
        date_emission: cheque.date_emission || '',
        date_echeance: cheque.date_echeance || '',
        statut: cheque.statut || 'en_cours',
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
                            <SearchableSelect value={data.tier_value} onChange={(value) => setData('tier_value', value)} options={tierOptions} placeholder="Tier" />
                            <label className="grid gap-1 text-sm"><span className="font-medium">Numéro</span><Input value={data.numero_cheque} onChange={(e) => setData('numero_cheque', e.target.value)} />{errors.numero_cheque && <span className="text-xs text-red-600">{errors.numero_cheque}</span>}</label>
                            <label className="grid gap-1 text-sm"><span className="font-medium">Banque</span><SearchableSelect value={data.banque} onChange={(value) => setData('banque', value)} options={banques} placeholder="Banque" /></label>
                            <label className="grid gap-1 text-sm"><span className="font-medium">Tireur / signataire</span><Input value={data.tireur_signataire} onChange={(e) => setData('tireur_signataire', e.target.value)} /></label>
                            <label className="grid gap-1 text-sm"><span className="font-medium">Montant</span><Input type="number" value={data.montant} onChange={(e) => setData('montant', e.target.value)} /></label>
                            <label className="grid gap-1 text-sm"><span className="font-medium">Émission</span><Input type="date" value={data.date_emission || ''} onChange={(e) => setData('date_emission', e.target.value)} /></label>
                            <label className="grid gap-1 text-sm"><span className="font-medium">Échéance</span><Input type="date" value={data.date_echeance || ''} onChange={(e) => setData('date_echeance', e.target.value)} /></label>
                            <SearchableSelect value={data.statut} onChange={(value) => setData('statut', value)} options={statuses} allowEmpty={false} placeholder="Statut" />
                        </div>
                        <label className="grid gap-1 text-sm"><span className="font-medium">Note</span><Textarea value={data.note || ''} onChange={(e) => setData('note', e.target.value)} /></label>
                        <div><Button disabled={processing}>Enregistrer</Button></div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
