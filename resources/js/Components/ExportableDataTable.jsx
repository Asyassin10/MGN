import { useEffect, useMemo, useState } from 'react';
import { Download } from 'lucide-react';
import DataTable from '@/Components/DataTable';
import { Button } from '@/Components/ui/button';
import { money } from '@/lib/utils';

export default function ExportableDataTable({ columns, rows, pagination, exportUrl, exportParams = {}, empty, onRowClick, rowClassName }) {
    const [selectedIds, setSelectedIds] = useState([]);
    const pageIds = useMemo(() => (rows || []).map((row) => row.id), [rows]);
    const allSelected = pageIds.length > 0 && pageIds.every((id) => selectedIds.includes(id));
    const selectedTotal = useMemo(() => (rows || [])
        .filter((row) => selectedIds.includes(row.id))
        .reduce((total, row) => total + Number(row.montant || 0), 0), [rows, selectedIds]);

    useEffect(() => setSelectedIds([]), [pageIds]);

    const download = (ids = []) => {
        const params = new URLSearchParams(Object.entries(exportParams).filter(([, value]) => value !== '' && value !== null && value !== undefined));
        ids.forEach((id) => params.append('selected_ids[]', id));
        window.location.assign(`${exportUrl}${exportUrl.includes('?') ? '&' : '?'}${params.toString()}`);
    };
    const toggle = (id) => setSelectedIds((current) => current.includes(id) ? current.filter((selectedId) => selectedId !== id) : [...current, id]);
    const toggleAll = () => setSelectedIds(allSelected ? [] : pageIds);
    const selectionColumn = {
        key: 'selection',
        label: <input aria-label="Sélectionner toutes les lignes affichées" type="checkbox" checked={allSelected} onChange={toggleAll} />,
        render: (row) => <input aria-label="Sélectionner cette ligne" type="checkbox" checked={selectedIds.includes(row.id)} onClick={(event) => event.stopPropagation()} onChange={() => toggle(row.id)} />,
    };

    return <>
        <div className="mb-3 flex flex-wrap items-center gap-2"><Button variant="outline" onClick={() => download()}><Download className="h-4 w-4" />Exporter Excel</Button>{selectedIds.length ? <><Button onClick={() => download(selectedIds)}><Download className="h-4 w-4" />Exporter la sélection</Button><span className="text-base font-bold text-emerald-800 md:text-lg">{selectedIds.length} sélectionné(s) · Total : {money(selectedTotal)}</span></> : null}</div>
        <DataTable columns={[selectionColumn, ...columns]} rows={rows} pagination={pagination} empty={empty} onRowClick={onRowClick} rowClassName={rowClassName} />
    </>;
}
