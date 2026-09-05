import { useState } from 'react';
import {
    AlertTriangle,
    Archive,
    Banknote,
    BarChart3,
    Boxes,
    Building2,
    CheckCircle2,
    Download,
    Layers3,
    PackageCheck,
    PackageMinus,
    Percent,
    ReceiptText,
    TrendingUp,
    Users,
    WalletCards,
    XCircle,
} from 'lucide-react';
import { Bar, BarChart, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import AppLayout from '@/Layouts/AppLayout';
import DataTable from '@/Components/DataTable';
import SearchableSelect from '@/Components/SearchableSelect';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { money, number } from '@/lib/utils';

const dashboards = [
    { value: 'global', label: 'Global' },
    { value: 'depot', label: 'Dépôt' },
    { value: 'fournisseurs', label: 'Fournisseurs' },
    { value: 'clients', label: 'Clients' },
];

const cardColors = {
    blue: 'border-l-blue-600 text-blue-700',
    emerald: 'border-l-emerald-600 text-emerald-700',
    amber: 'border-l-amber-500 text-amber-700',
    red: 'border-l-red-600 text-red-700',
    violet: 'border-l-violet-600 text-violet-700',
    cyan: 'border-l-cyan-600 text-cyan-700',
    zinc: 'border-l-zinc-500 text-zinc-700',
    orange: 'border-l-orange-500 text-orange-700',
};

const chartColors = ['#2563eb', '#059669', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#71717a', '#ea580c'];
const initialChartSize = { width: 320, height: 288 };

function Kpi({ label, value, color, icon: Icon, currency = true, suffix = '', valueClassName = 'text-zinc-950', detail }) {
    const displayValue = typeof value === 'number' ? (currency ? money(value) : number(value)) : value;

    return (
        <Card className={`summary-card border-l-4 ${cardColors[color]}`}>
            <CardContent className="flex min-h-24 items-center justify-between gap-3">
                <div className="min-w-0">
                    <div className="summary-label">{label}</div>
                    <div className={`summary-value break-words ${valueClassName}`}>{displayValue}{suffix}</div>
                    {detail ? <div className="mt-1 text-base font-medium text-zinc-600">{detail}</div> : null}
                </div>
                <Icon className={`h-5 w-5 shrink-0 ${cardColors[color].split(' ').at(-1)}`} />
            </CardContent>
        </Card>
    );
}

function ChartPanel({ title, children, empty = false, emptyText = 'Aucune donnée disponible.' }) {
    return (
        <Card className="min-w-0">
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent className="h-72 min-w-0">
                {empty ? <div className="flex h-full items-center justify-center text-base text-zinc-500">{emptyText}</div> : children}
            </CardContent>
        </Card>
    );
}

function MoneyTooltip({ active, payload, label }) {
    if (!active || !payload?.length) return null;

    return (
        <div className="border border-zinc-200 bg-white p-2 text-sm">
            <div className="font-medium text-zinc-950">{label}</div>
            {payload.map((item) => (
                <div key={item.dataKey || item.name} className="text-zinc-600">
                    {item.name || item.dataKey}: {money(item.value)}
                </div>
            ))}
        </div>
    );
}

function CountTooltip({ active, payload, label }) {
    if (!active || !payload?.length) return null;

    return (
        <div className="border border-zinc-200 bg-white p-2 text-sm">
            <div className="font-medium text-zinc-950">{label}</div>
            {payload.map((item) => (
                <div key={item.dataKey || item.name} className="text-zinc-600">
                    {item.name || item.dataKey}: {number(item.value)}
                </div>
            ))}
        </div>
    );
}

function PiePanel({ title, data, moneyValues = true }) {
    return (
        <ChartPanel title={title} empty={!data?.length}>
            <ResponsiveContainer width="100%" height="100%" minWidth={0} initialDimension={initialChartSize}>
                <PieChart>
                    <Pie data={data} dataKey="value" nameKey="name" innerRadius={54} outerRadius={88} paddingAngle={2}>
                        {data.map((entry, index) => (
                            <Cell key={entry.name} fill={entry.color || chartColors[index % chartColors.length]} />
                        ))}
                    </Pie>
                    <Tooltip formatter={(value) => (moneyValues ? money(value) : number(value))} />
                    <Legend />
                </PieChart>
            </ResponsiveContainer>
        </ChartPanel>
    );
}

function BarPanel({ title, data, dataKey = 'total', nameKey = 'name', fill = '#2563eb', moneyValues = true }) {
    return (
        <ChartPanel title={title} empty={!data?.length}>
            <ResponsiveContainer width="100%" height="100%" minWidth={0} initialDimension={initialChartSize}>
                <BarChart data={data}>
                    <XAxis dataKey={nameKey} tick={{ fontSize: 12 }} />
                    <YAxis tick={{ fontSize: 12 }} />
                    <Tooltip content={moneyValues ? <MoneyTooltip /> : <CountTooltip />} />
                    <Bar dataKey={dataKey} fill={fill} radius={[4, 4, 0, 0]} />
                </BarChart>
            </ResponsiveContainer>
        </ChartPanel>
    );
}

export default function Index({ dashboard }) {
    const [selected, setSelected] = useState('global');

    return (
        <AppLayout title="Global" actions={<div className="w-full sm:w-72"><SearchableSelect value={selected} onChange={setSelected} options={dashboards} allowEmpty={false} placeholder="Global | Dépôt | Fournisseurs | Clients" /></div>}>
            {selected === 'global' ? <GlobalDashboard data={dashboard.global} /> : null}
            {selected === 'depot' ? <DepotDashboard data={dashboard.depot} /> : null}
            {selected === 'fournisseurs' ? <FournisseurDashboard data={dashboard.fournisseurs} /> : null}
            {selected === 'clients' ? <ClientDashboard data={dashboard.clients} /> : null}
        </AppLayout>
    );
}

function GlobalDashboard({ data }) {
    return (
        <div className="grid gap-4">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Kpi label="Total fournisseurs" value={data.kpis.fournisseurs_count} color="blue" icon={Users} currency={false} />
                <Kpi label="Stock total des dépôts" value={data.kpis.stock_total} color="emerald" icon={PackageCheck} currency={false} />
                <Kpi label="Reste à payer fournisseurs" value={data.kpis.fournisseurs_reste} color="red" icon={WalletCards} valueClassName="text-red-600" />
                <Kpi label="Clients owe you" value={data.kpis.clients_reste} color="emerald" icon={Banknote} valueClassName="text-emerald-600" />
                <Kpi label="Montant disponible des chèques" value={data.kpis.cheques_disponibles_total} color="violet" icon={WalletCards} valueClassName="text-violet-700" detail={`${number(data.kpis.cheques_disponibles_count)} chèque(s) non sorti(s)`} />
                <Kpi label="Chèques fournisseurs non encaissés" value={data.kpis.cheques_fournisseurs_en_attente_count} color="amber" icon={WalletCards} currency={false} detail={`Total: ${money(data.kpis.cheques_fournisseurs_en_attente_total)}`} />
                <Kpi label="Clients en retard +30 jours" value={data.kpis.clients_overdue_count} color="red" icon={AlertTriangle} currency={false} />
            </div>
            <div className="max-w-xl">
                <PiePanel title="Situation financière globale" data={data.comparison.filter((item) => item.value > 0)} emptyText="Aucun montant à payer, à recevoir ou en chèques disponibles." />
                <div className="mt-3 grid gap-2 sm:grid-cols-3">
                    {data.comparison.map((item) => (
                        <div key={item.name} className="flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm">
                            <span className="h-3 w-3 shrink-0 rounded-full" style={{ backgroundColor: item.color }} />
                            <div className="min-w-0"><div className="font-medium text-zinc-900">{item.name}</div><div className="text-zinc-600">{money(item.value)}</div></div>
                        </div>
                    ))}
                </div>
            </div>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div className="font-semibold text-zinc-950">Notifications clients en retard</div>
                    <div className="text-sm text-zinc-500">Plus de 30 jours avec un solde restant.</div>
                </div>
                <a href={route('dashboard', { export: 'overdue_clients' })}>
                    <Button variant="outline"><Download className="h-4 w-4" />Download Excel</Button>
                </a>
            </div>
            <DataTable
                columns={[
                    { key: 'nom', label: 'Client' },
                    { key: 'telephone', label: 'Téléphone' },
                    { key: 'ville', label: 'Ville' },
                    { key: 'oldest_entry_date', label: 'Plus ancienne entrée' },
                    { key: 'days_overdue', label: 'Jours en retard', render: (row) => `${row.days_overdue} jours` },
                    { key: 'balance', label: 'À recevoir', render: (row) => money(row.balance) },
                ]}
                rows={data.overdue_clients}
                pagination={{ links: [] }}
                empty="Aucun client en retard de plus de 30 jours."
                rowClassName={() => 'status-row-impaye'}
            />
        </div>
    );
}

function DepotDashboard({ data }) {
    return (
        <div className="grid gap-4">
            <div className="grid gap-4 md:grid-cols-4">
                <Kpi label="Articles" value={data.kpis.total_articles} color="blue" icon={Boxes} currency={false} />
                <Kpi label="Dépôts" value={data.kpis.total_depots} color="cyan" icon={Building2} currency={false} />
                <Kpi label="Stock total" value={data.kpis.total_stock} color="emerald" icon={PackageCheck} currency={false} />
                <Kpi label="Affectations" value={data.kpis.assigned_articles} color="violet" icon={Layers3} currency={false} />
                <Kpi label="Moyenne par dépôt" value={data.kpis.average_stock_by_depot} color="zinc" icon={Percent} currency={false} />
                <Kpi label="Moyenne par ligne" value={data.kpis.average_quantity_by_line} color="orange" icon={BarChart3} currency={false} />
                <Kpi label="Stock faible" value={data.kpis.low_stock_count} color="amber" icon={AlertTriangle} currency={false} />
                <Kpi label="Rupture" value={data.kpis.zero_stock_count} color="red" icon={PackageMinus} currency={false} />
                <Kpi label="Opérations" value={data.kpis.operations_total} color="blue" icon={ReceiptText} currency={false} />
                <Kpi label="Ce mois" value={data.kpis.operations_this_month} color="cyan" icon={TrendingUp} currency={false} />
                <Kpi label="Entrées" value={data.kpis.entries_total} color="emerald" icon={CheckCircle2} currency={false} />
                <Kpi label="Sorties" value={data.kpis.exits_total} color="red" icon={XCircle} currency={false} />
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                <BarPanel title="Stock par dépôt" data={data.stockByDepot} dataKey="stock" fill="#2563eb" moneyValues={false} />
                <PiePanel title="Articles par dépôt" data={data.articleDistributionByDepot} moneyValues={false} />
                <PiePanel title="Santé stock faible" data={data.lowStockSeverity?.filter((item) => item.value > 0)} moneyValues={false} />
                <BarPanel title="Opérations par mois" data={data.monthlyOperations} dataKey="total" nameKey="month" fill="#7c3aed" moneyValues={false} />
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                <DataTable columns={[{ key: 'name', label: 'Dépôt' }, { key: 'stock', label: 'Stock' }, { key: 'articles', label: 'Articles' }]} rows={data.topStockedDepots} pagination={{ links: [] }} empty="Aucun dépôt." />
                <DataTable columns={[{ key: 'reference', label: 'Référence' }, { key: 'name', label: 'Article' }, { key: 'depot', label: 'Dépôt' }, { key: 'quantity', label: 'Qté' }]} rows={data.lowStock} pagination={{ links: [] }} empty="Aucune alerte stock faible." />
                <DataTable columns={[{ key: 'reference', label: 'Référence' }, { key: 'type', label: 'Type' }, { key: 'depot', label: 'Dépôt' }, { key: 'employee', label: 'Employé' }, { key: 'lines_count', label: 'Lignes' }, { key: 'created_at', label: 'Date' }]} rows={data.recentOperations} pagination={{ links: [] }} empty="Aucune opération récente." />
            </div>
        </div>
    );
}

function FournisseurDashboard({ data }) {
    return (
        <div className="grid gap-4">
            <div className="grid gap-4 md:grid-cols-4">
                <Kpi label="Fournisseurs" value={data.kpis.count} color="blue" icon={Users} currency={false} />
                <Kpi label="Relevés" value={data.kpis.releves_count} color="cyan" icon={Archive} currency={false} />
                <Kpi label="Factures" value={data.kpis.factures_count} color="violet" icon={ReceiptText} currency={false} />
                <Kpi label="Paiements" value={data.kpis.payments_count} color="emerald" icon={CheckCircle2} currency={false} />
                <Kpi label="Total dû" value={data.kpis.total_du} color="red" icon={ReceiptText} />
                <Kpi label="Total payé" value={data.kpis.total_paye} color="emerald" icon={Banknote} />
                <Kpi label="Balance globale" value={data.kpis.balance} color="orange" icon={Archive} />
                <Kpi label="Moyenne facture" value={data.kpis.average_facture} color="zinc" icon={BarChart3} />
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                <BarPanel title="Top fournisseurs par balance" data={data.top} dataKey="balance" nameKey="nom" fill="#dc2626" />
                <PiePanel title="Payé / reste" data={data.paidVsDuePie?.filter((item) => item.value > 0)} />
                <BarPanel title="Factures par mois" data={data.monthlyFactures} dataKey="total" nameKey="month" fill="#7c3aed" />
                <DataTable columns={[{ key: 'numero_facture', label: 'Numéro' }, { key: 'fournisseur', label: 'Fournisseur' }, { key: 'date_facture', label: 'Date' }, { key: 'montant', label: 'Montant', render: (row) => money(row.montant) }]} rows={data.recentFactures} pagination={{ links: [] }} empty="Aucune donnée fournisseur." />
            </div>
        </div>
    );
}

function ClientDashboard({ data }) {
    return (
        <div className="grid gap-4">
            <div className="grid gap-4 md:grid-cols-4">
                <Kpi label="Clients" value={data.kpis.count} color="blue" icon={Users} currency={false} />
                <Kpi label="Entrées" value={data.kpis.entries_count} color="cyan" icon={ReceiptText} currency={false} />
                <Kpi label="Paiements" value={data.kpis.payments_count} color="emerald" icon={CheckCircle2} currency={false} />
                <Kpi label="Créances" value={data.kpis.total_du} color="emerald" icon={WalletCards} />
                <Kpi label="Encaissé" value={data.kpis.total_encaisse} color="violet" icon={Banknote} />
                <Kpi label="Balance globale" value={data.kpis.balance} color="orange" icon={Archive} />
                <Kpi label="Moyenne entrée" value={data.kpis.average_entry} color="zinc" icon={BarChart3} />
                <Kpi label="Activité récente" value={data.recentEntries?.length || 0} color="amber" icon={TrendingUp} currency={false} />
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                <BarPanel title="Top clients par solde" data={data.top} dataKey="balance" nameKey="nom" fill="#059669" />
                <PiePanel title="Encaissé / reste" data={data.paidVsDuePie?.filter((item) => item.value > 0)} />
                <BarPanel title="Entrées par mois" data={data.monthlyEntries} dataKey="total" nameKey="month" fill="#2563eb" />
                <DataTable columns={[{ key: 'client', label: 'Client' }, { key: 'date_entree', label: 'Date' }, { key: 'montant', label: 'Montant', render: (row) => money(row.montant) }, { key: 'description', label: 'Description' }]} rows={data.recentEntries} pagination={{ links: [] }} empty="Aucune donnée client." />
            </div>
        </div>
    );
}
