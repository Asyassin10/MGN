import { useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Pencil, Plus, Save, Trash2 } from 'lucide-react';
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
    const [editIndex, setEditIndex] = useState(null);
    const addLine = () => {
        if (!line.article_id || Number(line.quantity) < 1) return;
        const next = { ...line, quantity: Number(line.quantity) };
        setData('lines', editIndex === null ? [...data.lines, next] : data.lines.map((item, index) => (index === editIndex ? next : item)));
        setEditIndex(null);
        setLine({ article_id: '', quantity: 0 });
    };
    const startEdit = (index) => {
        setEditIndex(index);
        setGroupFilter('');
        setLine({ article_id: String(data.lines[index].article_id), quantity: data.lines[index].quantity });
    };
    const cancelEdit = () => {
        setEditIndex(null);
        setLine({ article_id: '', quantity: 0 });
    };
    const removeLine = (index) => {
        setData('lines', data.lines.filter((_, i) => i !== index));
        if (editIndex !== null) {
            if (editIndex === index) cancelEdit();
            else if (editIndex > index) setEditIndex(editIndex - 1);
        }
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
                        <div className="flex gap-2">
                            <Button type="button" disabled={!line.article_id || Number(line.quantity) < 1} onClick={addLine}>{editIndex === null ? <><Plus className="h-4 w-4" />Ajouter</> : <><Save className="h-4 w-4" />Modifier</>}</Button>
                            {editIndex !== null ? <Button type="button" variant="outline" onClick={cancelEdit}>Annuler</Button> : null}
                        </div>
                    </div>
                    {errors.lines ? <div className="text-base text-red-600">{errors.lines}</div> : null}
                    <div className="rounded-md border border-zinc-200">
                        {data.lines.length === 0 ? <div className="px-3 py-3 text-base text-zinc-500">Aucun article ajouté.</div> : <div className="border-b border-zinc-100 bg-zinc-50 px-3 py-1 text-xs text-zinc-500">Cliquez sur une ligne pour la modifier.</div>}
                        {data.lines.map((item, index) => (
                            <div key={`${item.article_id}-${index}`} onClick={() => startEdit(index)} className={'flex cursor-pointer flex-col gap-2 border-b border-zinc-100 px-3 py-2 text-base last:border-b-0 hover:bg-zinc-50 sm:flex-row sm:items-center sm:justify-between ' + (editIndex === index ? 'bg-amber-50' : '')}>
                                <span className="min-w-0 truncate">{articles.find((article) => article.value === String(item.article_id))?.label}</span>
                                <div className="flex items-center justify-between gap-3 sm:justify-end">
                                    <span className="font-semibold">× {item.quantity}</span>
                                    <Button type="button" size="icon" variant="ghost" title="Modifier cette ligne" aria-label="Modifier cette ligne" onClick={(event) => { event.stopPropagation(); startEdit(index); }}><Pencil className="h-4 w-4" /></Button>
                                    <Button type="button" size="icon" variant="ghost" title="Retirer cette ligne" aria-label="Retirer cette ligne" onClick={(event) => { event.stopPropagation(); removeLine(index); }}><Trash2 className="h-4 w-4" /></Button>
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
