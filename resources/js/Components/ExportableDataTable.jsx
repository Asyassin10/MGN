import { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Download, Trash2, X } from 'lucide-react';
import DataTable from '@/Components/DataTable';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { money } from '@/lib/utils';
import usePersistentSelection from '@/lib/usePersistentSelection';

export default function ExportableDataTable({ columns, rows, pagination, exportUrl, exportParams = {}, deleteUrl, empty, onRowClick, rowClassName, preserveSelection = true, selectable = true, totalField = 'montant', totalLabel = 'Total', totalValue, summaryExtra }) {
    const { auth } = usePage().props;
    const canDelete = deleteUrl && auth.user?.role === 'admin';
    const [selectedRows, setSelectedRows] = usePersistentSelection(exportUrl + '|' + (exportParams.export ?? ''));
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const selectedIds = useMemo(() => Object.keys(selectedRows), [selectedRows]);
    const pageIds = useMemo(() => (rows || []).map((row) => String(row.id)), [rows]);
    const allSelected = pageIds.length > 0 && pageIds.every((id) => selectedIds.includes(id));
    const selectedTotal = useMemo(() => Object.values(selectedRows)
        .reduce((total, row) => total + (totalValue ? totalValue(row) : Number(row[totalField] || 0)), 0), [selectedRows, totalField, totalValue]);

    useEffect(() => {
        if (!preserveSelection) {
            setSelectedRows({});
            return;
        }
        setSelectedRows((current) => {
            const next = { ...current };
            let changed = false;
            (rows || []).forEach((row) => {
                if (next[row.id]) {
                    next[row.id] = row;
                    changed = true;
                }
            });
            return changed ? next : current;
        });
    }, [rows, preserveSelection]);

    const download = (ids = []) => {
        const params = new URLSearchParams(Object.entries(exportParams).filter(([, value]) => value !== '' && value !== null && value !== undefined));
        ids.forEach((id) => params.append('selected_ids[]', id));
        window.location.assign(`${exportUrl}${exportUrl.includes('?') ? '&' : '?'}${params.toString()}`);
    };
    const toggle = (row) => setSelectedRows((current) => {
        const next = { ...current };
        if (next[row.id]) delete next[row.id];
        else next[row.id] = row;
        return next;
    });
    const toggleAll = () => setSelectedRows((current) => {
        const next = { ...current };
        (rows || []).forEach((row) => {
            if (allSelected) delete next[row.id];
            else next[row.id] = row;
        });
        return next;
    });
    const deleteSelected = () => {
        router.delete(deleteUrl, {
            data: { selected_ids: selectedIds },
            preserveScroll: true,
            onSuccess: () => {
                setSelectedRows({});
                setConfirmingDelete(false);
            },
        });
    };
    const selectionColumn = {
        key: 'selection',
        label: <input aria-label="Sélectionner toutes les lignes affichées" type="checkbox" checked={allSelected} onChange={toggleAll} />,
        render: (row) => <input aria-label="Sélectionner cette ligne" type="checkbox" checked={selectedIds.includes(String(row.id))} onClick={(event) => event.stopPropagation()} onChange={() => toggle(row)} />,
    };

    return <>
        <div className="mb-3 flex flex-wrap items-center gap-2">
            <Button variant="outline" onClick={() => download()}><Download className="h-4 w-4" />Exporter Excel</Button>
            {selectable && selectedIds.length ? <>
                <Button onClick={() => download(selectedIds)}><Download className="h-4 w-4" />Exporter la sélection</Button>
                <Button variant="outline" onClick={() => setSelectedRows({})}><X className="h-4 w-4" />Tout désélectionner</Button>
                {canDelete ? <Button variant="destructive" onClick={() => setConfirmingDelete(true)}><Trash2 className="h-4 w-4" />Supprimer la sélection</Button> : null}
                <span className="text-base font-bold text-emerald-800 md:text-lg">{selectedIds.length} sélectionné(s) · {totalLabel} : {money(selectedTotal)}</span>
                {summaryExtra ? <span className="text-sm font-semibold text-zinc-700">{summaryExtra(Object.values(selectedRows))}</span> : null}
            </> : null}
        </div>
        <DataTable columns={selectable ? [selectionColumn, ...columns] : columns} rows={rows} pagination={pagination} empty={empty} onRowClick={onRowClick} rowClassName={rowClassName} preserveState={preserveSelection} />
        {canDelete ? (
            <Dialog open={confirmingDelete} onOpenChange={setConfirmingDelete}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>Supprimer {selectedIds.length} élément(s) ?</DialogTitle></DialogHeader>
                    <p className="mb-5 text-base text-zinc-600">Cette action est définitive.</p>
                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" onClick={() => setConfirmingDelete(false)}>Annuler</Button>
                        <Button type="button" variant="destructive" onClick={deleteSelected}>Confirmer la suppression</Button>
                    </div>
                </DialogContent>
            </Dialog>
        ) : null}
    </>;
}
