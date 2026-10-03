import { useEffect, useMemo, useState } from 'react';
import { Plus, Save, X } from 'lucide-react';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { priceTypeOptions } from '@/lib/articleFields';
import { money } from '@/lib/utils';

export const priceFor = (article, priceType) => Number(article?.[priceTypeOptions.find((option) => option.value === priceType)?.field] || 0);

const blank = (priceType) => ({ article_id: '', depot_id: '', quantity: 0, price_type: priceType, prix: 0 });

export default function PricedLineEditor({ articles, depots, groups, defaultPriceType = 'detail', editing = null, onSubmit, onCancelEdit, submitLabel = 'Ajouter la ligne' }) {
    const [line, setLine] = useState(blank(defaultPriceType));
    const [groupFilter, setGroupFilter] = useState('');
    const [attempted, setAttempted] = useState(false);

    const articleOf = (id) => articles.find((article) => article.value === String(id));
    const stockOf = (article, depotId) => Number(article?.stocks?.[String(depotId)] || 0);
    const totalStock = (article) => Object.values(article?.stocks || {}).reduce((sum, quantity) => sum + Number(quantity || 0), 0);
    const article = articleOf(line.article_id);

    useEffect(() => {
        setAttempted(false);
        if (editing) {
            setGroupFilter('');
            setLine({ ...editing.line, quantity: editing.line.quantity, prix: editing.line.prix });
        } else {
            setLine(blank(defaultPriceType));
        }
    }, [editing?.key]);

    useEffect(() => {
        if (editing) return;
        setLine((current) => ({ ...current, price_type: defaultPriceType, prix: priceFor(articleOf(current.article_id), defaultPriceType) }));
    }, [defaultPriceType]);

    const shownArticles = useMemo(() => {
        const list = articles
            .filter((item) => totalStock(item) > 0 && (!groupFilter || item.group_id === groupFilter))
            .map((item) => ({ ...item, label: item.label + ' (stock: ' + totalStock(item) + ')' }));
        if (line.article_id && !list.some((item) => item.value === String(line.article_id)) && article) list.unshift(article);
        return list;
    }, [articles, groupFilter, line.article_id]);

    const articleDepots = useMemo(() => {
        if (!article) return [];
        const list = depots
            .filter((depot) => stockOf(article, depot.value) > 0)
            .map((depot) => ({ ...depot, label: depot.label + ' (stock: ' + stockOf(article, depot.value) + ')' }));
        const current = depots.find((depot) => depot.value === String(line.depot_id));
        if (current && !list.some((depot) => depot.value === current.value)) list.unshift(current);
        return list;
    }, [article, depots, line.depot_id]);

    const maxPrice = Number(article?.prix_max || 0);
    const minPrice = Number(article?.prix_min || 0);
    const prix = Number(line.prix || 0);
    const priceError = !article ? null
        : maxPrice > 0 && prix > maxPrice ? 'Prix maximum : ' + money(maxPrice) + ' — vous ne pouvez pas dépasser ce prix.'
            : minPrice > 0 && prix < minPrice ? 'Prix minimum : ' + money(minPrice) + ' — vous ne pouvez pas descendre sous ce prix.'
                : null;
    const stockHere = stockOf(article, line.depot_id);
    const quantityError = line.depot_id && stockHere > 0 && Number(line.quantity) > stockHere ? 'Stock disponible dans ce dépôt : ' + stockHere : null;
    const problem = !line.article_id ? 'Choisissez un article.'
        : !line.depot_id ? 'Choisissez un dépôt.'
            : Number(line.quantity) < 1 ? 'La quantité doit être au moins 1.'
                : quantityError || priceError || null;

    const pickGroup = (value) => {
        setGroupFilter(value);
        setLine({ ...line, article_id: '', depot_id: '', quantity: 0, prix: 0 });
    };
    const pickArticle = (articleId) => {
        const picked = articleOf(articleId);
        const available = picked ? depots.filter((depot) => stockOf(picked, depot.value) > 0) : [];
        setLine({ ...line, article_id: articleId, depot_id: available.length === 1 ? available[0].value : '', quantity: 0, prix: priceFor(picked, line.price_type) });
    };
    const pickPriceType = (priceType) => setLine({ ...line, price_type: priceType, prix: priceFor(article, priceType) });
    const submit = () => {
        if (problem) {
            setAttempted(true);
            return;
        }
        setAttempted(false);
        onSubmit({ ...line, quantity: Number(line.quantity), prix });
        if (!editing) setLine({ ...blank(line.price_type), price_type: line.price_type });
    };

    return (
        <div className={'grid gap-4 rounded-md border p-4 ' + (editing ? 'border-amber-300 bg-amber-50/60' : 'border-blue-300 bg-white/80')}>
            {editing ? <div className="text-sm font-semibold text-amber-800">Modification de la ligne sélectionnée</div> : null}
            <div className="grid gap-4 md:grid-cols-12">
                <label className="grid min-w-0 content-start gap-1 text-sm md:col-span-5"><span className="font-medium text-zinc-700">Article (السلعة) · {shownArticles.length} en stock</span><SearchableSelect value={line.article_id} onChange={pickArticle} options={shownArticles} placeholder="Article" /></label>
                <label className="grid min-w-0 content-start gap-1 text-sm md:col-span-4"><span className="font-medium text-zinc-700">Depot (المستودع){article ? ' · ' + articleDepots.length + ' dépôt(s)' : ''}</span><SearchableSelect value={line.depot_id} onChange={(value) => setLine({ ...line, depot_id: value, quantity: 0 })} options={articleDepots} placeholder={article ? 'Dépôt contenant cet article' : 'Choisissez d’abord un article'} allowEmpty={false} /></label>
                <label className="grid min-w-0 content-start gap-1 text-sm md:col-span-3"><span className="font-medium text-zinc-700">Filter by group (حسب العائلة)</span><SearchableSelect value={groupFilter} onChange={pickGroup} options={groups} placeholder="Tous les groupes" emptyLabel="Tous les groupes" /></label>
            </div>
            <div className="grid gap-4 md:grid-cols-12">
                <label className="grid min-w-0 content-start gap-1 text-sm md:col-span-3"><span className="font-medium text-zinc-700">Quantity (الكمية)</span><Input type="number" min="0" max={stockHere || undefined} value={line.quantity} onChange={(event) => setLine({ ...line, quantity: event.target.value })} aria-label="Quantity (الكمية)" /></label>
                <label className="grid min-w-0 content-start gap-1 text-sm md:col-span-5"><span className="font-medium text-zinc-700">Price type (نوع السعر)</span><SearchableSelect value={line.price_type} onChange={pickPriceType} options={priceTypeOptions} placeholder="Prix" allowEmpty={false} /></label>
                <label className="grid min-w-0 content-start gap-1 text-sm md:col-span-4"><span className="font-medium text-zinc-700">Price (السعر)</span><Input type="number" min="0" step="any" value={line.prix} onChange={(event) => setLine({ ...line, prix: event.target.value })} aria-label="Price (السعر)" className={priceError ? 'border-red-500 focus:border-red-600' : ''} />{article && (minPrice > 0 || maxPrice > 0) ? <span className="text-xs text-zinc-500">Min : {minPrice > 0 ? money(minPrice) : '—'} · Max : {maxPrice > 0 ? money(maxPrice) : '—'}</span> : null}</label>
            </div>
            <div className="flex flex-wrap items-center gap-3">
                <Button type="button" onClick={submit}>{editing ? <><Save className="h-4 w-4" />Modifier la ligne</> : <><Plus className="h-4 w-4" />{submitLabel}</>}</Button>
                {editing ? <Button type="button" variant="outline" onClick={onCancelEdit}><X className="h-4 w-4" />Annuler la modification</Button> : null}
                {priceError || quantityError || (attempted && problem) ? <span className="text-sm font-semibold text-red-600">{priceError || quantityError || problem}</span> : null}
            </div>
        </div>
    );
}
