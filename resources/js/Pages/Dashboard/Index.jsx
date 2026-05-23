import { useState } from 'react';
import {
    AlertTriangle,
    Archive,
    Banknote,
    BarChart3,
    Boxes,
    Building2,
    CheckCircle2,
    Clock,
    Layers3,
    PackageCheck,
    PackageMinus,
    Percent,
    PieChart as PieChartIcon,
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
import { money, number } from '@/lib/utils';

const dashboards = [
    { value: 'cheques', label: 'Chèques' },
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

function Kpi({ label, value, color, icon: Icon, currency = true, suffix = '' }) {
    const displayValue = typeof value === 'number' ? (currency ? money(value) : number(value)) : value;

    return (
        <Card className={`border-l-4 ${cardColors[color]}`}>
            <CardContent className="flex min-h-24 items-center justify-between gap-3">
                <div className="min-w-0">
                    <div className="text-xs font-medium uppercase text-zinc-500">{label}</div>
                    <div className="mt-2 break-words text-2xl font-semibold text-zinc-950">{displayValue}{suffix}</div>
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
                {empty ? <div className="flex h-full items-center justify-center text-sm text-zinc-500">{emptyText}</div> : children}
            </CardContent>
        </Card>
    );
}

function MoneyTooltip({ active, payload, label }) {
    if (!active || !payload?.length) return null;

    return (
        <div className="border border-zinc-200 bg-white p-2 text-xs">
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
        <div className="border border-zinc-200 bg-white p-2 text-xs">
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
                            <Cell key={entry.name} fill={chartColors[index % chartColors.length]} />
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
    const [selected, setSelected] = useState('depot');

    return (
        <AppLayout title="Dashboard" actions={<div className="w-full sm:w-72"><SearchableSelect value={selected} onChange={setSelected} options={dashboards} allowEmpty={false} placeholder="Chèques | Dépôt | Fournisseurs | Clients" /></div>}>
            {selected === 'depot' ? <DepotDashboard data={dashboard.depot} /> : null}
            {selected === 'cheques' ? <ChequeDashboard data={dashboard.cheques} /> : null}
            {selected === 'fournisseurs' ? <FournisseurDashboard data={dashboard.fournisseurs} /> : null}
            {selected === 'clients' ? <ClientDashboard data={dashboard.clients} /> : null}
        </AppLayout>
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

function ChequeDashboard({ data }) {
    return (
        <div className="grid gap-4">
            <div className="grid gap-4 md:grid-cols-4">
                <Kpi label="Nombre chèques" value={data.kpis.count} color="blue" icon={WalletCards} currency={false} />
                <Kpi label="Montant total" value={data.kpis.total_amount} color="cyan" icon={Banknote} />
                <Kpi label="En cours" value={data.kpis.en_cours} color="amber" icon={Clock} />
                <Kpi label="En caisse" value={data.kpis.en_caisse} color="emerald" icon={CheckCircle2} />
                <Kpi label="Impayé" value={data.kpis.impaye} color="red" icon={XCircle} />
                <Kpi label="Chèques clients" value={data.kpis.client_count} color="violet" icon={Users} currency={false} />
                <Kpi label="Chèques fournisseurs" value={data.kpis.fournisseur_count} color="orange" icon={ReceiptText} currency={false} />
                <Kpi label="Moyenne chèque" value={data.kpis.average_amount} color="zinc" icon={PieChartIcon} />
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                <PiePanel title="Répartition par statut" data={data.statusPie} />
                <PiePanel title="Répartition client / fournisseur" data={data.typePie} />
                <BarPanel title="Chèques par mois" data={data.monthly} dataKey="total" nameKey="month" fill="#2563eb" />
                <BarPanel title="Top banques" data={data.topBanks} dataKey="total" fill="#0891b2" />
            </div>

            <DataTable columns={[{ key: 'numero_cheque', label: 'Numéro' }, { key: 'source', label: 'Source' }, { key: 'tier', label: 'Type' }, { key: 'banque', label: 'Banque' }, { key: 'date_echeance', label: 'Échéance' }, { key: 'montant', label: 'Montant', render: (row) => money(row.montant) }]} rows={data.upcoming} pagination={{ links: [] }} empty="Aucun chèque à afficher." />
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
