import { useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { priceTypeOptions } from '@/lib/articleFields';
import { money } from '@/lib/utils';

const paymentModes = [{ value: 'espece', label: 'Espèce' }, { value: 'virement', label: 'Virement' }, { value: 'cheque', label: 'Chèque' }, { value: 'effet', label: 'Effet' }];

const priceFor = (article, priceType) => Number(article?.[priceTypeOptions.find((option) => option.value === priceType)?.field] || 0);

export default function Create({ clients, depots, articles, groups, employees }) {
    const { data, setData, post, processing, errors } = useForm({ client_id: '', client_nom: '', employee_id: '', mode_paiement: 'espece', note: '', lines: [] });
    const [line, setLine] = useState({ article_id: '', depot_id: depots[0]?.value || '', quantity: 0, price_type: 'detail', prix: 0 });
    const [groupFilter, setGroupFilter] = useState('');
    const articleOf = (id) => articles.find((article) => article.value === String(id));
    const stockOf = (article, depotId) => Number(article?.stocks?.[String(depotId)] || 0);
    const depotArticles = useMemo(() => articles
        .filter((article) => stockOf(article, line.depot_id) > 0 && (!groupFilter || article.group_id === groupFilter))
        .map((article) => ({ ...article, label: article.label + ' (stock: ' + stockOf(article, line.depot_id) + ')' })), [articles, line.depot_id, groupFilter]);
    const pickDepot = (depotId) => setLine({ ...line, depot_id: depotId, article_id: '', prix: 0 });

    const pickClient = (clientId) => {
        setData('client_id', clientId);
        const priceType = clients.find((client) => client.value === String(clientId))?.price_type || 'detail';
        setLine((current) => ({ ...current, price_type: priceType, prix: priceFor(articleOf(current.article_id), priceType) }));
    };
    const clientPriceLabel = priceTypeOptions.find((option) => option.value === (clients.find((client) => client.value === String(data.client_id))?.price_type))?.label;
    const pickArticle = (articleId) => setLine({ ...line, article_id: articleId, quantity: 0, prix: priceFor(articleOf(articleId), line.price_type) });
    const pickPriceType = (priceType) => setLine({ ...line, price_type: priceType, prix: priceFor(articleOf(line.article_id), priceType) });
    const addLine = () => {
        if (!line.article_id || !line.depot_id || Number(line.quantity) < 1) return;
        setData('lines', [...data.lines, { ...line, quantity: Number(line.quantity), prix: Number(line.prix || 0) }]);
        setLine({ ...line, article_id: '', quantity: 0, prix: 0 });
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
                        <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Client (الزبون)</span><SearchableSelect value={data.client_id} onChange={pickClient} options={clients} placeholder="Client" />{clientPriceLabel ? <span className="text-sm text-zinc-500">Type de prix du client : {clientPriceLabel}</span> : null}</label>
                        <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Client name if not in list (اسم الزبون)</span><Input value={data.client_nom} onChange={(event) => setData('client_nom', event.target.value)} disabled={Boolean(data.client_id)} /></label>
                    </div>
                    <div className="grid gap-3 md:grid-cols-2">
                    <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Livreur (الموصل)</span><SearchableSelect value={data.employee_id} onChange={(value) => setData('employee_id', value)} options={employees} placeholder="Employé qui livre (optionnel)" emptyLabel="Aucun" />{errors.employee_id ? <span className="text-sm text-red-600">{errors.employee_id}</span> : null}</label>
                    <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Mode de paiement</span><SearchableSelect value={data.mode_paiement} onChange={(value) => setData('mode_paiement', value)} options={paymentModes} placeholder="Mode de paiement" allowEmpty={false} />{errors.mode_paiement ? <span className="text-sm text-red-600">{errors.mode_paiement}</span> : null}</label>
                    </div>
                    <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Note (ملاحظة)</span><Textarea value={data.note} onChange={(event) => setData('note', event.target.value)} /></label>

                    <div className="grid gap-2 rounded-md border border-zinc-200 p-3 md:grid-cols-6">
                        <label className="grid gap-1 text-sm md:col-span-2"><span className="font-medium text-zinc-700">Depot (المستودع)</span><SearchableSelect value={line.depot_id} onChange={pickDepot} options={depots} placeholder="Dépôt" allowEmpty={false} /></label>
                        <label className="grid gap-1 text-sm md:col-span-2"><span className="font-medium text-zinc-700">Filter by group (حسب العائلة)</span><SearchableSelect value={groupFilter} onChange={(value) => { setGroupFilter(value); setLine({ ...line, article_id: '', prix: 0 }); }} options={groups} placeholder="Tous les groupes" emptyLabel="Tous les groupes" /></label>
                        <label className="grid gap-1 text-sm md:col-span-2"><span className="font-medium text-zinc-700">Article (السلعة) · {depotArticles.length} en stock</span><SearchableSelect value={line.article_id} onChange={pickArticle} options={depotArticles} placeholder="Article du dépôt" /></label>
                        <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Quantity (الكمية)</span><Input type="number" min="0" max={stockOf(articleOf(line.article_id), line.depot_id) || undefined} value={line.quantity} onChange={(event) => setLine({ ...line, quantity: event.target.value })} aria-label="Quantity (الكمية)" /></label>
                        <label className="grid gap-1 text-sm md:col-span-2"><span className="font-medium text-zinc-700">Price type (نوع السعر)</span><SearchableSelect value={line.price_type} onChange={pickPriceType} options={priceTypeOptions} placeholder="Prix" allowEmpty={false} /></label>
                        <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Price (السعر)</span><Input type="number" step="any" value={line.prix} readOnly tabIndex={-1} className="cursor-not-allowed bg-zinc-100" aria-label="Price (السعر)" /></label>
                        <div className="md:col-span-6"><Button type="button" disabled={!line.article_id || Number(line.quantity) < 1} onClick={addLine}><Plus className="h-4 w-4" />Ajouter la ligne</Button></div>
                    </div>

                    {errors.lines ? <div className="text-base text-red-600">{errors.lines}</div> : null}
                    <div className="rounded-md border border-zinc-200">
                        {data.lines.length === 0 ? <div className="px-3 py-3 text-base text-zinc-500">Aucune ligne ajoutée.</div> : null}
                        {data.lines.map((item, index) => (
                            <div key={`${item.article_id}-${index}`} className="flex flex-col gap-2 border-b border-zinc-100 px-3 py-2 text-base last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                                <span className="min-w-0 truncate">{articleOf(item.article_id)?.label} <span className="text-sm text-zinc-500">· {depots.find((depot) => depot.value === String(item.depot_id))?.label}</span></span>
                                <div className="flex items-center justify-between gap-3 sm:justify-end">
                                    <span>{item.quantity} × {money(item.prix)}</span>
                                    <span className="w-28 text-right font-semibold">{money(item.quantity * item.prix)}</span>
                                    <Button type="button" size="icon" variant="ghost" onClick={() => setData('lines', data.lines.filter((_, i) => i !== index))}><Trash2 className="h-4 w-4" /></Button>
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
