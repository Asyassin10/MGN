import { Link, router } from '@inertiajs/react';
import { Check, ListChecks, Plus, X } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import CreatedAtFilter from '@/Components/CreatedAtFilter';
import DataTable from '@/Components/DataTable';
import DeleteButton from '@/Components/DeleteButton';
import PricedLineEditor from '@/Components/PricedLineEditor';
import PrintPdfButton from '@/Components/PrintPdfButton';
import SearchableSelect from '@/Components/SearchableSelect';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { money } from '@/lib/utils';

const statuses = [{ value: 'en_attente', label: 'En attente' }, { value: 'partiel', label: 'Partiel' }, { value: 'valide', label: 'Validé' }, { value: 'annule', label: 'Annulé' }];
const statusBadge = { en_attente: ['yellow', 'En attente'], partiel: ['yellow', 'Partiel'], valide: ['green', 'Validé'], annule: ['red', 'Annulé'] };
const patchOptions = { preserveScroll: true, preserveState: true };
const paymentModes = [{ value: 'espece', label: 'Espèce' }, { value: 'virement', label: 'Virement' }, { value: 'cheque', label: 'Chèque' }, { value: 'effet', label: 'Effet' }];

const LINE_GRID = 'md:grid-cols-[minmax(0,1fr)_120px_140px_96px]';

function LinesHeader() {
    return (
        <div className={'hidden gap-3 border-b border-zinc-200 bg-zinc-100 px-3 py-2 text-sm font-semibold text-zinc-700 md:grid ' + LINE_GRID}>
            <span>Article (السلعة)</span>
            <span>Quantity (الكمية)</span>
            <span>Price (السعر)</span>
            <span className="text-right">Actions</span>
        </div>
    );
}

function FieldLabel({ children }) {
    return <span className="text-xs font-medium text-zinc-500 md:hidden">{children}</span>;
}

function LineRow({ bon, line }) {
    const [quantity, setQuantity] = useState(String(line.quantity));
    const [prix, setPrix] = useState(String(line.prix));
    const [attempted, setAttempted] = useState(false);

    const qty = Number(quantity);
    const price = Number(prix);
    const priceError = line.prix_max > 0 && price > line.prix_max ? 'Prix maximum : ' + money(line.prix_max)
        : line.prix_min > 0 && price < line.prix_min ? 'Prix minimum : ' + money(line.prix_min) : null;
    const quantityError = line.stock > 0 && qty > line.stock ? 'Stock disponible : ' + line.stock : (line.stock <= 0 ? 'Stock épuisé pour cet article' : null);
    const problem = qty < 1 ? 'La quantité doit être au moins 1.' : priceError || quantityError;

    const save = () => {
        if (qty >= 1 && !priceError && (qty !== line.quantity || price !== line.prix)) {
            router.patch(route('livraisons.lines.update', [bon.id, line.id]), { quantity: qty, prix: price }, patchOptions);
        }
    };
    const validate = () => {
        if (problem) {
            setAttempted(true);
            return;
        }
        router.patch(route('livraisons.lines.validate', [bon.id, line.id]), { quantity: qty, prix: price }, patchOptions);
    };
    const remove = () => router.patch(route('livraisons.lines.remove', [bon.id, line.id]), {}, patchOptions);

    if (line.validated) {
        return (
            <div className={'grid items-center gap-2 border-b border-zinc-100 bg-emerald-50 px-3 py-2 text-base last:border-b-0 md:gap-3 ' + LINE_GRID}>
                <span className="min-w-0 truncate font-medium text-emerald-900">{line.article}</span>
                <span className="font-semibold"><FieldLabel>Quantity (الكمية)</FieldLabel><span className="block">{line.quantity}</span></span>
                <span className="font-semibold"><FieldLabel>Price (السعر)</FieldLabel><span className="block">{money(line.prix)}</span></span>
                <span className="md:justify-self-end"><Badge variant="green"><Check className="mr-1 h-4 w-4" />Validé</Badge></span>
            </div>
        );
    }

    return (
        <div className="grid gap-1 border-b border-zinc-100 px-3 py-2 text-base last:border-b-0">
            <div className={'grid gap-2 md:items-center md:gap-3 ' + LINE_GRID}>
                <span className="min-w-0 truncate font-medium">{line.article}</span>
                <label className="grid gap-1"><FieldLabel>Quantity (الكمية)</FieldLabel><Input type="number" min="1" value={quantity} onChange={(event) => setQuantity(event.target.value)} onBlur={save} onKeyDown={(event) => { if (event.key === 'Enter') save(); }} aria-label="Quantity (الكمية)" /></label>
                <label className="grid gap-1"><FieldLabel>Price (السعر)</FieldLabel><Input type="number" min="0" step="any" value={prix} onChange={(event) => setPrix(event.target.value)} onBlur={save} onKeyDown={(event) => { if (event.key === 'Enter') save(); }} aria-label="Price (السعر)" className={priceError ? 'border-red-500' : ''} /></label>
                <div className="flex gap-2 md:justify-end">
                    <Button size="icon" className="bg-emerald-600 text-white hover:bg-emerald-700" title="Valider cet article : retirer du stock" aria-label="Valider cet article" onClick={validate}><Check className="h-4 w-4" /></Button>
                    <Button size="icon" variant="outline" title="Retirer cet article du devis" aria-label="Retirer cet article" onClick={remove}><X className="h-4 w-4" /></Button>
                </div>
            </div>
            {priceError || (attempted && problem) ? <span className="text-sm font-semibold text-red-600">{priceError || problem}</span> : null}
        </div>
    );
}
function HeaderFields({ bon }) {
    const [livreur, setLivreur] = useState(bon.livreur_nom || '');
    const save = (changes) => router.patch(route('livraisons.update', bon.id), { livreur_nom: livreur, mode_paiement: bon.mode_paiement, ...changes }, patchOptions);

    return (
        <div className="mb-3 grid gap-3 md:grid-cols-2">
            <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Livreur (الموصل)</span><Input value={livreur} onChange={(event) => setLivreur(event.target.value)} onBlur={() => { if (livreur !== (bon.livreur_nom || '')) save({}); }} onKeyDown={(event) => { if (event.key === 'Enter') save({}); }} placeholder="Nom du livreur (optionnel)" /></label>
            <label className="grid gap-1 text-sm"><span className="font-medium text-zinc-700">Mode de paiement</span><SearchableSelect value={bon.mode_paiement} onChange={(value) => save({ mode_paiement: value })} options={paymentModes} placeholder="Mode de paiement" allowEmpty={false} /></label>
        </div>
    );
}

export default function Index({ bons, filters, depots, articles, groups }) {
    const [openId, setOpenId] = useState(null);
    const [showEditor, setShowEditor] = useState(false);
    const current = bons.data.find((item) => item.id === openId) || null;
    const canEdit = current && ['en_attente', 'partiel'].includes(current.status);
    const update = (key, value) => router.get(route('livraisons.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });
    const addLine = (line) => router.post(route('livraisons.lines.store', current.id), line, patchOptions);

    const columns = [
        { key: 'created_at', label: 'Date / heure' },
        { key: 'reference', label: 'Référence' },
        { key: 'client', label: 'Client', render: (row) => row.client || '-' },
        { key: 'depots', label: 'Dépôt(s)', render: (row) => row.depots || '—' },
        { key: 'employee', label: 'Livreur', render: (row) => row.employee || '-' },
        { key: 'mode', label: 'Mode de paiement' },
        { key: 'lines_count', label: 'Lignes' },
        { key: 'total', label: 'Total', render: (row) => <span className="text-lg font-semibold">{money(row.total)}</span> },
        { key: 'status', label: 'Statut', render: (row) => <div className="grid gap-1"><Badge variant={statusBadge[row.status]?.[0]}>{statusBadge[row.status]?.[1] || row.status}</Badge>{row.status === 'partiel' ? <span className="text-xs">{row.validated_count}/{row.lines_count} validé(s)</span> : null}</div> },
        { key: 'created_by', label: 'Créé par', render: (row) => row.created_by || '-' },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    {['en_attente', 'partiel'].includes(row.status) ? <Button size="sm" className="bg-emerald-600 text-white hover:bg-emerald-700" title="Valider article par article" onClick={() => { setShowEditor(false); setOpenId(row.id); }}><Check className="h-4 w-4" />Valider</Button> : null}
                    {row.status === 'en_attente' ? <Button size="sm" variant="destructive" title="Annuler" onClick={() => router.patch(route('livraisons.cancel', row.id), {}, { preserveScroll: true })}><X className="h-4 w-4" />Annuler</Button> : null}
                    <PrintPdfButton url={row.pdf_url} size="sm" />
                    <DeleteButton action={route('livraisons.destroy', row.id)} title={`Supprimer ${row.reference} ?`} message="Le stock déjà retiré par les articles validés de ce devis sera rétabli dans les dépôts." />
                </div>
            ),
        },
    ];

    return (
        <AppLayout title="Devis" actions={<Link href={route('livraisons.create')}><Button><Plus className="h-4 w-4" />Nouveau devis</Button></Link>}>
            <div className="mb-4 grid gap-2 md:grid-cols-3">
                <Input placeholder="Référence ou client" defaultValue={filters.search || ''} onChange={(event) => update('search', event.target.value)} />
                <SearchableSelect value={filters.status || ''} onChange={(value) => update('status', value)} options={statuses} placeholder="Tous les statuts" />
                <CreatedAtFilter routeName="livraisons.index" filters={filters} />
            </div>
            <DataTable columns={columns} rows={bons.data} pagination={bons} empty="Aucun devis." rowClassName={(row) => row.status === 'valide' ? 'status-row status-row-devis-valide' : row.status === 'annule' ? 'status-row status-row-sorti' : ''} />

            <Dialog open={Boolean(current)} onOpenChange={(open) => !open && setOpenId(null)}>
                <DialogContent className="max-w-5xl">
                    <DialogHeader><DialogTitle>Valider le devis {current?.reference} · {current?.client || 'sans client'}</DialogTitle></DialogHeader>
                    <p className="mb-3 text-base text-zinc-600">Cliquez sur la coche verte de chaque article : sa quantité est retirée du stock du dépôt de la ligne. Vous pouvez modifier la quantité ou le prix, retirer ou ajouter des articles tant qu’ils ne sont pas validés.</p>
                    {current ? (
                        <>
                            {canEdit ? <HeaderFields key={current.id} bon={current} /> : null}
                            <div className="mb-3 overflow-hidden rounded-md border border-zinc-200">
                                <LinesHeader />
                                {current.lines.map((line) => <LineRow key={line.id + '-' + line.quantity + '-' + line.prix + '-' + line.validated} bon={current} line={line} />)}
                            </div>
                            {canEdit ? (showEditor
                                ? <div className="mb-3"><PricedLineEditor articles={articles} depots={depots} groups={groups} defaultPriceType={current.client_price_type} onSubmit={addLine} submitLabel="Ajouter à ce devis" /><Button type="button" variant="outline" size="sm" className="mt-2" onClick={() => setShowEditor(false)}>Masquer</Button></div>
                                : <div className="mb-3"><Button type="button" variant="outline" onClick={() => setShowEditor(true)}><Plus className="h-4 w-4" />Ajouter la ligne</Button></div>) : null}
                            <div className="flex items-center justify-between gap-3">
                                <span className="flex items-center gap-2 text-base font-semibold text-emerald-800"><ListChecks className="h-5 w-5" />{current.validated_count}/{current.lines_count} article(s) validé(s) · Total {money(current.total)}</span>
                                <Button type="button" variant="outline" onClick={() => setOpenId(null)}>Fermer</Button>
                            </div>
                        </>
                    ) : null}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
