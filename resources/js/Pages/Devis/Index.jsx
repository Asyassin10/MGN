import { Link, router } from '@inertiajs/react';
import { Check, ListChecks, Plus, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import CreatedAtFilter from '@/Components/CreatedAtFilter';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import PrintPdfButton from '@/Components/PrintPdfButton';
import SearchableSelect from '@/Components/SearchableSelect';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';

const statuses = [{ value: 'en_attente', label: 'En attente' }, { value: 'partiel', label: 'Partiel' }, { value: 'valide', label: 'Validé' }, { value: 'annule', label: 'Annulé' }];
const statusBadge = { en_attente: ['yellow', 'En attente'], partiel: ['yellow', 'Partiel'], valide: ['green', 'Validé'], annule: ['red', 'Annulé'] };

const LINE_GRID = 'md:grid-cols-[minmax(0,1fr)_120px_220px_96px]';

function LinesHeader() {
    return (
        <div className={'hidden gap-3 border-b border-zinc-200 bg-zinc-100 px-3 py-2 text-sm font-semibold text-zinc-700 md:grid ' + LINE_GRID}>
            <span>Article (السلعة)</span>
            <span>Quantity (الكمية)</span>
            <span>Depot (المستودع)</span>
            <span className="text-right">Actions</span>
        </div>
    );
}

function FieldLabel({ children }) {
    return <span className="text-xs font-medium text-zinc-500 md:hidden">{children}</span>;
}

function LineRow({ devis, line, depots }) {
    const [quantity, setQuantity] = useState(String(line.quantity));
    const [depotId, setDepotId] = useState(line.depot_id || '');
    const saveQuantity = () => {
        if (Number(quantity) >= 1 && Number(quantity) !== line.quantity) {
            router.patch(route('devis.lines.update', [devis.id, line.id]), { quantity: Number(quantity) }, { preserveScroll: true, preserveState: true });
        }
    };
    const validate = () => router.patch(route('devis.lines.validate', [devis.id, line.id]), { depot_id: depotId, quantity: Number(quantity) }, { preserveScroll: true, preserveState: true });
    const remove = () => router.patch(route('devis.lines.remove', [devis.id, line.id]), {}, { preserveScroll: true, preserveState: true });

    if (line.validated) {
        return (
            <div className={'grid items-center gap-2 border-b border-zinc-100 bg-emerald-50 px-3 py-2 text-base last:border-b-0 md:gap-3 ' + LINE_GRID}>
                <span className="min-w-0 truncate font-medium text-emerald-900">{line.article}</span>
                <span className="font-semibold"><FieldLabel>Quantity (الكمية)</FieldLabel><span className="block">{line.quantity}</span></span>
                <span><FieldLabel>Depot (المستودع)</FieldLabel><Badge variant="green" className="block w-fit">{line.depot}</Badge></span>
                <span className="md:justify-self-end"><Badge variant="green"><Check className="mr-1 h-4 w-4" />Validé</Badge></span>
            </div>
        );
    }

    return (
        <div className={'grid gap-2 border-b border-zinc-100 px-3 py-2 text-base last:border-b-0 md:items-center md:gap-3 ' + LINE_GRID}>
            <span className="min-w-0 truncate font-medium">{line.article}</span>
            <label className="grid gap-1"><FieldLabel>Quantity (الكمية)</FieldLabel><Input type="number" min="1" value={quantity} onChange={(event) => setQuantity(event.target.value)} onBlur={saveQuantity} onKeyDown={(event) => { if (event.key === 'Enter') saveQuantity(); }} aria-label="Quantity (الكمية)" /></label>
            <label className="grid gap-1"><FieldLabel>Depot (المستودع)</FieldLabel><SearchableSelect value={depotId} onChange={setDepotId} options={depots} placeholder="Choisir le dépôt" allowEmpty={false} /></label>
            <div className="flex gap-2 md:justify-end">
                <Button size="icon" className="bg-emerald-600 text-white hover:bg-emerald-700" disabled={!depotId || Number(quantity) < 1} title="Valider cet article dans le dépôt" aria-label="Valider cet article" onClick={validate}><Check className="h-4 w-4" /></Button>
                <Button size="icon" variant="outline" title="Retirer cet article du bon de commande" aria-label="Retirer cet article" onClick={remove}><X className="h-4 w-4" /></Button>
            </div>
        </div>
    );
}
export default function Index({ devis, filters, fournisseurs, depots, articles, groups }) {
    const [openId, setOpenId] = useState(null);
    const [groupFilter, setGroupFilter] = useState('');
    const [newLine, setNewLine] = useState({ article_id: '', quantity: 0 });
    const current = devis.data.find((item) => item.id === openId) || null;
    const update = (key, value) => router.get(route('devis.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const shownArticles = useMemo(() => articles.filter((article) => !groupFilter || article.group_id === groupFilter), [articles, groupFilter]);
    const canEdit = current && ['en_attente', 'partiel'].includes(current.status);

    const addLine = () => {
        if (!newLine.article_id || Number(newLine.quantity) < 1) return;
        router.post(route('devis.lines.store', current.id), { article_id: newLine.article_id, quantity: Number(newLine.quantity) }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setNewLine({ article_id: '', quantity: 0 }),
        });
    };

    const columns = [
        { key: 'created_at', label: 'Date / heure' },
        { key: 'reference', label: 'Référence bon de commande' },
        { key: 'fournisseur', label: 'Fournisseur' },
        { key: 'depots', label: 'Dépôt(s)', render: (row) => row.depots || '—' },
        { key: 'articles', label: 'Articles / quantités', render: (row) => <span className="block max-w-md text-sm">{row.articles}</span> },
        { key: 'status', label: 'Statut', render: (row) => <div className="grid gap-1"><Badge variant={statusBadge[row.status]?.[0]}>{statusBadge[row.status]?.[1] || row.status}</Badge>{row.status === 'partiel' ? <span className="text-xs text-zinc-500">{row.validated_count}/{row.lines_count} validé(s)</span> : null}</div> },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    {['en_attente', 'partiel'].includes(row.status) ? <Button size="sm" className="bg-emerald-600 text-white hover:bg-emerald-700" title="Valider article par article" onClick={() => setOpenId(row.id)}><Check className="h-4 w-4" />Valider</Button> : null}
                    {row.status === 'en_attente' ? <Button size="sm" variant="destructive" title="Annuler" onClick={() => router.patch(route('devis.cancel', row.id), {}, { preserveScroll: true })}><X className="h-4 w-4" />Annuler</Button> : null}
                    <PrintPdfButton url={row.pdf_url} size="sm" />
                    {['valide', 'partiel'].includes(row.status) ? null : <DeleteButton action={route('devis.destroy', row.id)} title={`Supprimer ${row.reference} ?`} />}
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Bons de commande" actions={<Link href={route('devis.create')}><Button><Plus className="h-4 w-4" />Nouveau bon de commande</Button></Link>}>
            <div className="mb-4 grid gap-2 md:grid-cols-4">
                <Input placeholder="Référence ou fournisseur" defaultValue={filters.search || ''} onChange={(event) => update('search', event.target.value)} />
                <SearchableSelect value={filters.fournisseur_id || ''} onChange={(value) => update('fournisseur_id', value)} options={fournisseurs} placeholder="Tous les fournisseurs" />
                <SearchableSelect value={filters.status || ''} onChange={(value) => update('status', value)} options={statuses} placeholder="Tous les statuts" />
                <CreatedAtFilter routeName="devis.index" filters={filters} />
            </div>
            <DataTable columns={columns} rows={devis.data} pagination={devis} empty="Aucun bon de commande." rowClassName={(row) => row.status === 'valide' ? 'status-row status-row-devis-valide' : row.status === 'annule' ? 'status-row status-row-sorti' : ''} />

            <Dialog open={Boolean(current)} onOpenChange={(open) => !open && setOpenId(null)}>
                <DialogContent className="max-w-4xl">
                    <DialogHeader><DialogTitle>Valider le bon de commande {current?.reference} · {current?.fournisseur}</DialogTitle></DialogHeader>
                    <p className="mb-3 text-base text-zinc-600">Pour chaque article, choisissez le dépôt puis cliquez sur la coche verte : la quantité est ajoutée au stock de ce dépôt. Vous pouvez aussi modifier les quantités, retirer ou ajouter des articles tant qu’ils ne sont pas validés.</p>
                    {current ? (
                        <>
                            <div className="mb-3 overflow-hidden rounded-md border border-zinc-200">
                                <LinesHeader />
                                {current.lines.map((line) => <LineRow key={line.id + '-' + line.quantity + '-' + line.validated} devis={current} line={line} depots={depots} />)}
                            </div>
                            {canEdit ? (
                                <div className="mb-3 grid gap-2 rounded-md border border-dashed border-zinc-300 p-3 md:grid-cols-[1fr_1.6fr_90px_auto] md:items-end">
                                    <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Filter by group (حسب العائلة)</span><SearchableSelect value={groupFilter} onChange={(value) => { setGroupFilter(value); setNewLine({ ...newLine, article_id: '' }); }} options={groups} placeholder="Tous les groupes" emptyLabel="Tous les groupes" /></label>
                                    <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Add article (إضافة سلعة)</span><SearchableSelect value={newLine.article_id} onChange={(value) => setNewLine({ ...newLine, article_id: value })} options={shownArticles} placeholder="Article" /></label>
                                    <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Qty (الكمية)</span><Input type="number" min="0" value={newLine.quantity} onChange={(event) => setNewLine({ ...newLine, quantity: event.target.value })} /></label>
                                    <Button type="button" disabled={!newLine.article_id || Number(newLine.quantity) < 1} onClick={addLine}><Plus className="h-4 w-4" />Ajouter</Button>
                                </div>
                            ) : null}
                            <div className="flex items-center justify-between gap-3">
                                <span className="flex items-center gap-2 text-base font-semibold text-emerald-800"><ListChecks className="h-5 w-5" />{current.validated_count}/{current.lines_count} article(s) validé(s)</span>
                                <Button type="button" variant="outline" onClick={() => setOpenId(null)}>Fermer</Button>
                            </div>
                        </>
                    ) : null}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
