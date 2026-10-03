import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import PricedLineEditor from '@/Components/PricedLineEditor';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { priceTypeOptions } from '@/lib/articleFields';
import { money } from '@/lib/utils';

const paymentModes = [{ value: 'espece', label: 'Espèce' }, { value: 'virement', label: 'Virement' }, { value: 'cheque', label: 'Chèque' }, { value: 'effet', label: 'Effet' }];

export default function Create({ clients, depots, articles, groups }) {
    const { data, setData, post, processing, errors } = useForm({ client_id: '', client_nom: '', livreur_nom: '', mode_paiement: 'espece', note: '', lines: [] });
    const [editing, setEditing] = useState(null);
    const articleOf = (id) => articles.find((article) => article.value === String(id));
    const clientPriceType = clients.find((client) => client.value === String(data.client_id))?.price_type || 'detail';
    const clientPriceLabel = data.client_id ? priceTypeOptions.find((option) => option.value === clientPriceType)?.label : null;

    const saveLine = (line) => {
        if (editing) {
            setData('lines', data.lines.map((item, index) => (index === editing.index ? line : item)));
            setEditing(null);
            return;
        }
        setData('lines', [...data.lines, line]);
    };
    const startEdit = (index) => setEditing({ key: Date.now(), index, line: data.lines[index] });
    const removeLine = (index) => {
        setData('lines', data.lines.filter((_, i) => i !== index));
        if (editing) setEditing(editing.index === index ? null : { ...editing, index: editing.index > index ? editing.index - 1 : editing.index });
    };
    const total = data.lines.reduce((sum, item) => sum + item.quantity * item.prix, 0);
    const submit = (event) => {
        event.preventDefault();
        post(route('livraisons.store'));
    };

    return (
        <AppLayout title="Nouveau devis">
            <Card className="max-w-5xl"><CardContent>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-3 md:grid-cols-2">
                        <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Client (الزبون)</span><SearchableSelect value={data.client_id} onChange={(value) => setData('client_id', value)} options={clients} placeholder="Client" />{clientPriceLabel ? <span className="text-sm text-zinc-500">Type de prix du client : {clientPriceLabel}</span> : null}</label>
                        <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Client name if not in list (اسم الزبون)</span><Input value={data.client_nom} onChange={(event) => setData('client_nom', event.target.value)} disabled={Boolean(data.client_id)} /></label>
                    </div>
                    <div className="grid gap-3 md:grid-cols-2">
                        <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Livreur (الموصل)</span><Input value={data.livreur_nom} onChange={(event) => setData('livreur_nom', event.target.value)} placeholder="Nom du livreur (optionnel)" />{errors.livreur_nom ? <span className="text-sm text-red-600">{errors.livreur_nom}</span> : null}</label>
                        <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Mode de paiement</span><SearchableSelect value={data.mode_paiement} onChange={(value) => setData('mode_paiement', value)} options={paymentModes} placeholder="Mode de paiement" allowEmpty={false} />{errors.mode_paiement ? <span className="text-sm text-red-600">{errors.mode_paiement}</span> : null}</label>
                    </div>
                    <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Note (ملاحظة)</span><Textarea value={data.note} onChange={(event) => setData('note', event.target.value)} /></label>

                    <PricedLineEditor articles={articles} depots={depots} groups={groups} defaultPriceType={clientPriceType} editing={editing} onSubmit={saveLine} onCancelEdit={() => setEditing(null)} />

                    {errors.lines ? <div className="text-base text-red-600">{errors.lines}</div> : null}
                    <div className="rounded-md border border-zinc-200">
                        {data.lines.length === 0 ? <div className="px-3 py-3 text-base text-zinc-500">Aucune ligne ajoutée.</div> : <div className="border-b border-zinc-100 bg-zinc-50 px-3 py-1 text-xs text-zinc-500">Cliquez sur une ligne pour la modifier.</div>}
                        {data.lines.map((item, index) => (
                            <div key={`${item.article_id}-${index}`} onClick={() => startEdit(index)} className={'flex cursor-pointer flex-col gap-2 border-b border-zinc-100 px-3 py-2 text-base last:border-b-0 hover:bg-zinc-50 sm:flex-row sm:items-center sm:justify-between ' + (editing?.index === index ? 'bg-amber-50' : '')}>
                                <span className="min-w-0 truncate">{articleOf(item.article_id)?.label} <span className="text-sm text-zinc-500">· {depots.find((depot) => depot.value === String(item.depot_id))?.label}</span></span>
                                <div className="flex items-center justify-between gap-3 sm:justify-end">
                                    <span>{item.quantity} × {money(item.prix)}</span>
                                    <span className="w-28 text-right font-semibold">{money(item.quantity * item.prix)}</span>
                                    <Button type="button" size="icon" variant="ghost" title="Modifier cette ligne" aria-label="Modifier cette ligne" onClick={(event) => { event.stopPropagation(); startEdit(index); }}><Pencil className="h-4 w-4" /></Button>
                                    <Button type="button" size="icon" variant="ghost" title="Retirer cette ligne" aria-label="Retirer cette ligne" onClick={(event) => { event.stopPropagation(); removeLine(index); }}><Trash2 className="h-4 w-4" /></Button>
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="text-right text-xl font-bold text-emerald-800">Total : {money(total)}</div>
                    <div><Button disabled={processing}>Enregistrer le devis</Button></div>
                </form>
            </CardContent></Card>
        </AppLayout>
    );
}
