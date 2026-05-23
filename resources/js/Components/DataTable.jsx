import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import Pagination from '@/Components/Pagination';

export default function DataTable({ columns, rows, pagination, empty = 'Aucun résultat.', onRowClick }) {
    const actionsColumn = columns.find((column) => column.key === 'actions');
    const detailColumns = columns.filter((column) => column.key !== 'actions');

    return (
        <div className="rounded-md border border-zinc-200 bg-white">
            <div className="hidden overflow-x-auto md:block">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((column) => <TableHead key={column.key}>{column.label}</TableHead>)}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows?.length ? rows.map((row) => (
                            <TableRow key={row.id} onClick={() => onRowClick?.(row)} className={onRowClick ? 'cursor-pointer' : ''}>
                                {columns.map((column) => (
                                    <TableCell key={column.key} onClick={column.key === 'actions' ? (event) => event.stopPropagation() : undefined}>
                                        {column.render ? column.render(row) : row[column.key]}
                                    </TableCell>
                                ))}
                            </TableRow>
                        )) : (
                            <TableRow>
                                <TableCell colSpan={columns.length} className="py-8 text-center text-zinc-500">{empty}</TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            <div className="divide-y divide-zinc-100 md:hidden">
                {rows?.length ? rows.map((row) => (
                    <div key={row.id} onClick={() => onRowClick?.(row)} className={`p-4 ${onRowClick ? 'cursor-pointer active:bg-zinc-50' : ''}`}>
                        <dl className="grid gap-2">
                            {detailColumns.map((column) => (
                                <div key={column.key} className="flex items-start justify-between gap-3 text-sm">
                                    <dt className="shrink-0 text-xs font-medium uppercase text-zinc-500">{column.label}</dt>
                                    <dd className="min-w-0 text-right text-zinc-900">{column.render ? column.render(row) : row[column.key]}</dd>
                                </div>
                            ))}
                        </dl>
                        {actionsColumn ? (
                            <div className="mt-4 flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-3" onClick={(event) => event.stopPropagation()}>
                                {actionsColumn.render ? actionsColumn.render(row) : row.actions}
                            </div>
                        ) : null}
                    </div>
                )) : <div className="px-4 py-8 text-center text-sm text-zinc-500">{empty}</div>}
            </div>
            <div className="border-t border-zinc-200 px-3 pb-3">
                <Pagination links={pagination?.links || []} />
            </div>
        </div>
    );
}
