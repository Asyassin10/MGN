import { useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';

export default function Create({ fournisseurs, articles, groups }) {
    const { data, setData, post, processing, errors } = useForm({ fournisseur_id: '', note: '', lines: [] });
    const [line, setLine] = useState({ article_id: '', quantity: 0 });
    const [groupFilter, setGroupFilter] = useState('');
    const shownArticles = useMemo(() => articles.filter((article) => !groupFilter || article.group_id === groupFilter), [articles, groupFilter]);
    const addLine = () => {
        if (!line.article_id || Number(line.quantity) < 1) return;
        setData('lines', [...data.lines, { ...line, quantity: Number(line.quantity) }]);
        setLine({ article_id: '', quantity: 0 });
    };
    const submit = (event) => {
        event.preventDefault();
        post(route('devis.store'));
    };

    return (
        <AppLayout title="Nouveau bon de commande">
            <Card className="max-w-4xl"><CardContent>
                <form onSubmit={submit} className="grid gap-4">
                    <label className="grid gap-1 text-base md:max-w-md"><span className="font-medium text-zinc-700">Supplier (المزود)</span><SearchableSelect value={data.fournisseur_id} onChange={(value) => setData('fournisseur_id', value)} options={fournisseurs} placeholder="Fournisseur" allowEmpty={false} />{errors.fournisseur_id ? <span className="text-sm text-red-600">{errors.fournisseur_id}</span> : null}</label>
                    <label className="grid gap-1 text-base"><span className="font-medium text-zinc-700">Note (ملاحظة)</span><Textarea value={data.note} onChange={(event) => setData('note', event.target.value)} /></label>
                    <div className="grid gap-2 md:grid-cols-[1fr_1.5fr_110px_auto] md:items-end">
                        <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Filter by group (حسب العائلة)</span><SearchableSelect value={groupFilter} onChange={(value) => { setGroupFilter(value); setLine({ ...line, article_id: '' }); }} options={groups} placeholder="Tous les groupes" emptyLabel="Tous les groupes" /></label>
                        <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Article (السلعة) · {shownArticles.length}</span><SearchableSelect value={line.article_id} onChange={(value) => setLine({ ...line, article_id: value })} options={shownArticles} placeholder="Article" /></label>
                        <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Quantity (الكمية)</span><Input type="number" min="0" value={line.quantity} onChange={(event) => setLine({ ...line, quantity: event.target.value })} aria-label="Quantity (الكمية)" /></label>
                        <Button type="button" disabled={!line.article_id || Number(line.quantity) < 1} onClick={addLine}><Plus className="h-4 w-4" />Ajouter</Button>
                    </div>
                    {errors.lines ? <div className="text-base text-red-600">{errors.lines}</div> : null}
                    <div className="rounded-md border border-zinc-200">
                        {data.lines.length === 0 ? <div className="px-3 py-3 text-base text-zinc-500">Aucun article ajouté.</div> : null}
                        {data.lines.map((item, index) => (
                            <div key={`${item.article_id}-${index}`} className="flex flex-col gap-2 border-b border-zinc-100 px-3 py-2 text-base last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                                <span className="min-w-0 truncate">{articles.find((article) => article.value === String(item.article_id))?.label}</span>
                                <div className="flex items-center justify-between gap-3 sm:justify-end">
                                    <span className="font-semibold">× {item.quantity}</span>
                                    <Button type="button" size="icon" variant="ghost" onClick={() => setData('lines', data.lines.filter((_, i) => i !== index))}><Trash2 className="h-4 w-4" /></Button>
                                </div>
                            </div>
                        ))}
                    </div>
                    <div><Button disabled={processing}>Enregistrer le bon de commande</Button></div>
                </form>
            </CardContent></Card>
        </AppLayout>
    );
}
