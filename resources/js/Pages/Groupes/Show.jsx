import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Minus, Plus } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import DataTable from '@/Components/DataTable';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';

export default function Show({ group, articles, available, filters }) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [picked, setPicked] = useState({});
    const [rowSelected, setRowSelected] = useState({});
    const [dropIds, setDropIds] = useState(null);
    const pickedIds = Object.keys(picked);
    const rowSelectedIds = Object.keys(rowSelected);
    const pageIds = articles.data.map((row) => String(row.id));
    const allSelected = pageIds.length > 0 && pageIds.every((id) => rowSelectedIds.includes(id));

    useEffect(() => {
        setRowSelected({});
    }, [articles.data]);
    const shown = useMemo(() => {
        const term = search.trim().toLowerCase();
        return available.filter((article) => !term || `${article.reference} ${article.name}`.toLowerCase().includes(term)).slice(0, 200);
    }, [available, search]);

    const close = () => {
        setOpen(false);
        setPicked({});
        setSearch('');
    };
    const toggle = (id) => setPicked((current) => {
        const next = { ...current };
        if (next[id]) delete next[id];
        else next[id] = true;
        return next;
    });
    const assign = () => router.patch(route('groupes.assign'), { group_id: group.id, selected_ids: pickedIds }, {
        preserveScroll: true,
        onSuccess: close,
    });

    const toggleRow = (id) => setRowSelected((current) => {
        const next = { ...current };
        if (next[id]) delete next[id];
        else next[id] = true;
        return next;
    });
    const toggleAll = () => setRowSelected((current) => {
        const next = { ...current };
        articles.data.forEach((row) => {
            if (allSelected) delete next[row.id];
            else next[row.id] = true;
        });
        return next;
    });
    const drop = () => router.patch(route('groupes.remove', group.id), { selected_ids: dropIds }, {
        preserveScroll: true,
        onFinish: () => setDropIds(null),
    });

    const columns = [
        ...(group.protected ? [] : [{ key: 'selection', label: <input aria-label="Sélectionner tous les articles affichés" type="checkbox" checked={allSelected} onChange={toggleAll} />, render: (row) => <input aria-label="Sélectionner cet article" type="checkbox" checked={rowSelectedIds.includes(String(row.id))} onChange={() => toggleRow(String(row.id))} /> }]),
        { key: 'reference', label: 'Code', render: (row) => <Link className="font-medium hover:underline" href={route('articles.show', row.id)}>{row.reference}</Link> },
        { key: 'name', label: 'Article' },
        { key: 'unite', label: 'Unité' },
        ...(group.protected ? [] : [{ key: 'actions', label: 'Actions', render: (row) => <Button size="sm" variant="outline" onClick={() => setDropIds([String(row.id)])}><Minus className="h-4 w-4" />Retirer du groupe</Button> }]),
    ];

    return (
        <AppLayout title={`Groupe : ${group.name}`} actions={<><Link href={route('groupes.index')}><Button variant="outline"><ArrowLeft className="h-4 w-4" />Tous les groupes</Button></Link><Button onClick={() => setOpen(true)}><Plus className="h-4 w-4" />Assigner des articles à ce groupe</Button></>}>
            <div className="mb-4 max-w-md">
                <Input placeholder="Recherche code ou article" defaultValue={filters.search || ''} onChange={(event) => router.get(route('groupes.show', group.id), { search: event.target.value }, { preserveState: true, replace: true })} />
            </div>
            <div className="mb-2 flex flex-wrap items-center gap-3">
                <span className="text-base font-semibold text-zinc-700">{articles.total ?? articles.data.length} article(s) dans ce groupe</span>
                {rowSelectedIds.length ? <Button size="sm" variant="destructive" onClick={() => setDropIds(rowSelectedIds)}><Minus className="h-4 w-4" />Retirer la sélection ({rowSelectedIds.length})</Button> : null}
            </div>
            <DataTable columns={columns} rows={articles.data} pagination={articles} empty="Aucun article dans ce groupe pour le moment. Utilisez le bouton « Assigner des articles à ce groupe »." />

            <Dialog open={Boolean(dropIds)} onOpenChange={(value) => !value && setDropIds(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>Retirer {dropIds?.length} article(s) du groupe « {group.name} » ?</DialogTitle></DialogHeader>
                    <p className="mb-5 text-base text-zinc-600">Ces articles reviendront dans le groupe Général. Vous pourrez les assigner à un autre groupe ensuite.</p>
                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" onClick={() => setDropIds(null)}>Annuler</Button>
                        <Button type="button" variant="destructive" onClick={drop}>Confirmer</Button>
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={open} onOpenChange={(value) => !value && close()}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader><DialogTitle>Assigner des articles au groupe « {group.name} »</DialogTitle></DialogHeader>
                    <Input className="mb-3" placeholder="Rechercher un article (code ou nom)" value={search} onChange={(event) => setSearch(event.target.value)} />
                    <div className="mb-3 max-h-80 overflow-auto rounded-md border border-blue-300 bg-white">
                        {shown.length === 0 ? <div className="px-3 py-3 text-base text-zinc-500">Aucun article disponible.</div> : null}
                        {shown.map((article) => (
                            <label key={article.id} className="flex cursor-pointer items-center gap-3 border-b border-zinc-100 px-3 py-2 text-base last:border-b-0 hover:bg-zinc-50">
                                <input type="checkbox" checked={Boolean(picked[article.id])} onChange={() => toggle(article.id)} />
                                <span className="min-w-0 flex-1 truncate">{article.reference} - {article.name}</span>
                                <span className="text-sm text-zinc-500">{article.group_name || '-'}</span>
                            </label>
                        ))}
                    </div>
                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <span className="text-base font-semibold text-emerald-800">{pickedIds.length} sélectionné(s)</span>
                        <div className="flex flex-col-reverse gap-2 sm:flex-row">
                            <Button type="button" variant="outline" onClick={close}>Annuler</Button>
                            <Button type="button" disabled={!pickedIds.length} onClick={assign}>Assigner au groupe</Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
