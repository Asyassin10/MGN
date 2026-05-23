import { Link, router, usePage } from '@inertiajs/react';
import { BarChart3, Boxes, Building2, ChevronDown, Handshake, Landmark, LayoutDashboard, ListChecks, LogOut, PackageSearch, ReceiptText, Settings, UserRound, Users, WalletCards } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

const sections = [
    { label: 'Dashboard', route: 'dashboard', icon: LayoutDashboard },
    {
        label: 'Dépôt',
        icon: Building2,
        children: [
            { label: 'Stock', route: 'depots.index', icon: PackageSearch },
            { label: 'Articles', route: 'articles.index', icon: Boxes },
            { label: 'Opérations', route: 'operations.index', icon: ListChecks },
            { label: 'Employés', route: 'employees.index', icon: UserRound },
        ],
    },
    {
        label: 'Fournisseurs',
        icon: Handshake,
        children: [
            { label: 'Liste des fournisseurs', route: 'fournisseurs.index', active: ['fournisseurs.index', 'fournisseurs.create', 'fournisseurs.show'], icon: ReceiptText },
            { label: 'Relevés compte', route: 'fournisseurs.releves.index', active: ['fournisseurs.releves.*'], icon: ListChecks },
        ],
    },
    {
        label: 'Clients',
        icon: Users,
        children: [
            { label: 'Liste des clients', route: 'clients.index', icon: Users },
        ],
    },
    {
        label: 'Chèques',
        icon: WalletCards,
        children: [
            { label: 'Banques', route: 'banks.index', icon: Landmark },
            { label: 'Clients chèques', route: 'cheque-party-clients.index', icon: Users },
            { label: 'Fournisseurs chèques', route: 'cheque-party-fournisseurs.index', icon: Handshake },
            { label: 'Chèques clients', route: 'cheque-clients.index', icon: WalletCards },
            { label: 'Chèques fournisseurs', route: 'cheque-fournisseurs.index', icon: ReceiptText },
            { label: 'Registre standalone', route: 'cheques.index', icon: BarChart3 },
        ],
    },
];

function isRouteActive(item) {
    if (item.active) return item.active.some((name) => route().current(name));
    if (!item.route) return item.children?.some(isRouteActive);
    const wildcard = item.route.replace('.index', '.*');
    return route().current(item.route) || route().current(wildcard);
}

export default function AppSidebar({ className, onNavigate }) {
    const { auth } = usePage().props;
    const initialOpen = useMemo(() => Object.fromEntries(sections.filter((section) => section.children).map((section) => [section.label, isRouteActive(section)])), []);
    const [open, setOpen] = useState(initialOpen);

    return (
        <aside className={cn('fixed inset-y-0 left-0 z-30 w-64 flex-col border-r border-zinc-200 bg-white', className)}>
            <div className="border-b border-zinc-200 px-4 py-4">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-950 text-sm font-bold text-white">DP</div>
                    <div className="min-w-0">
                        <div className="text-sm font-semibold text-zinc-950">Droguerie P</div>
                        <div className="truncate text-xs text-zinc-500">{auth?.user?.name || 'Workspace'}</div>
                    </div>
                </div>
            </div>
            <nav className="flex-1 space-y-1 p-2">
                {sections.map((section) => {
                    const Icon = section.icon;
                    const active = isRouteActive(section);
                    if (!section.children) {
                        return (
                            <Link key={section.label} href={route(section.route)} onClick={onNavigate} className={`flex h-9 items-center gap-2 rounded-md px-3 text-sm ${active ? 'bg-zinc-100 font-medium text-zinc-950' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950'}`}>
                                <Icon className="h-4 w-4" />
                                {section.label}
                            </Link>
                        );
                    }

                    return (
                        <div key={section.label}>
                            <button type="button" onClick={() => setOpen((value) => ({ ...value, [section.label]: !value[section.label] }))} className={`flex h-9 w-full items-center gap-2 rounded-md px-3 text-sm ${active ? 'bg-zinc-100 font-medium text-zinc-950' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950'}`}>
                                <Icon className="h-4 w-4" />
                                <span className="flex-1 text-left">{section.label}</span>
                                <ChevronDown className={`h-4 w-4 transition ${open[section.label] ? 'rotate-180' : ''}`} />
                            </button>
                            {open[section.label] ? (
                                <div className="ml-4 mt-1 space-y-1 border-l border-zinc-200 pl-2">
                                    {section.children.map((child) => {
                                        const ChildIcon = child.icon;
                                        const childActive = isRouteActive(child);
                                        return (
                                            <Link key={child.label} href={route(child.route, child.params || {})} onClick={onNavigate} className={`flex h-8 items-center gap-2 rounded-md px-3 text-xs ${childActive ? 'bg-zinc-100 font-medium text-zinc-950' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-950'}`}>
                                                <ChildIcon className="h-3.5 w-3.5" />
                                                {child.label}
                                            </Link>
                                        );
                                    })}
                                </div>
                            ) : null}
                        </div>
                    );
                })}
            </nav>
            <div className="border-t border-zinc-200 p-2">
                <Link href={route('settings.index')} onClick={onNavigate} className={`mb-1 flex h-9 items-center gap-2 rounded-md px-3 text-sm ${route().current('settings.*') ? 'bg-zinc-100 font-medium text-zinc-950' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950'}`}>
                    <Settings className="h-4 w-4" />
                    Paramètres
                </Link>
                <Button variant="ghost" className="w-full justify-start" onClick={() => { onNavigate?.(); router.post(route('logout')); }}>
                    <LogOut className="h-4 w-4" />
                    Déconnexion
                </Button>
            </div>
        </aside>
    );
}
