import { Link, router, usePage } from '@inertiajs/react';
import { BadgeAlert, Boxes, Building2, ChevronDown, Handshake, LayoutDashboard, ListChecks, LogOut, PackageSearch, ReceiptText, Settings, ShieldCheck, UserRound, Users, WalletCards } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { getSectionThemeByKey } from '@/lib/sectionTheme';

const sections = [
    { label: 'Dashboard', route: 'dashboard', icon: LayoutDashboard, permission: 'dashboard' },
    {
        label: 'Dépôt',
        icon: Building2,
        theme: 'depot', permission: 'depots',
        children: [
            { label: 'Stock', route: 'depots.index', icon: PackageSearch },
            { label: 'Articles', route: 'articles.index', icon: Boxes },
            { label: 'Opérations', route: 'operations.index', icon: ListChecks },
        ],
    },
    {
        label: 'Fournisseurs',
        icon: Handshake,
        theme: 'fournisseurs', permission: 'fournisseurs',
        children: [
            { label: 'Liste des fournisseurs', route: 'fournisseurs.index', active: ['fournisseurs.index', 'fournisseurs.create', 'fournisseurs.show'], icon: ReceiptText },
            { label: 'Relevés compte', route: 'fournisseurs.releves.index', active: ['fournisseurs.releves.*'], icon: ListChecks },
        ],
    },
    {
        label: 'Clients',
        icon: Users,
        theme: 'clients', permission: 'clients',
        children: [
            { label: 'Liste des clients', route: 'clients.index', icon: Users },
        ],
    },
    {
        label: 'Chèques',
        icon: WalletCards,
        theme: 'cheques', permission: 'cheques',
        children: [
            { label: 'Chèques', route: 'cheques.index', icon: WalletCards },
            { label: 'Impayés', route: 'cheques.impayes.index', icon: BadgeAlert },
        ],
    },
    { label: 'RH / Employés', route: 'employees.index', icon: UserRound, permission: 'employees' },
    { label: 'Utilisateurs', route: 'users.index', icon: ShieldCheck, permission: 'admin' },
    { label: 'Historique', route: 'activity-history.index', icon: ListChecks, permission: 'admin' },
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
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-950 text-base font-bold text-white">DP</div>
                    <div className="min-w-0">
                        <div className="text-base font-semibold text-zinc-950">Droguerie P</div>
                        <div className="truncate text-sm text-zinc-500">{auth?.user?.name || 'Workspace'}</div>
                    </div>
                </div>
            </div>
            <nav className="flex-1 space-y-1 p-2">
                {sections.filter((section) => auth.user?.role === 'admin' || auth.user?.permissions?.modules?.includes(section.permission)).map((section) => {
                    const Icon = section.icon;
                    const active = isRouteActive(section);
                    const theme = getSectionThemeByKey(section.theme);
                    if (!section.children) {
                        return (
                            <Link key={section.label} href={route(section.route)} onClick={onNavigate} className={`flex h-10 items-center gap-2 rounded-md px-3 text-base ${active ? 'bg-zinc-100 font-medium text-zinc-950' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950'}`}>
                                <Icon className="h-4 w-4" />
                                {section.label}
                            </Link>
                        );
                    }

                    return (
                        <div key={section.label}>
                            <button type="button" onClick={() => setOpen((value) => ({ ...value, [section.label]: !value[section.label] }))} className={`flex h-10 w-full items-center gap-2 rounded-md px-3 text-base ${active ? `${theme.sidebarGroup} font-medium` : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950'}`}>
                                <Icon className="h-4 w-4" />
                                <span className="flex-1 text-left">{section.label}</span>
                                <ChevronDown className={`h-4 w-4 transition ${open[section.label] ? 'rotate-180' : ''}`} />
                            </button>
                            {open[section.label] ? (
                                <div className={`ml-4 mt-1 space-y-1 border-l pl-2 ${active ? theme.sidebarDivider : 'border-zinc-200'}`}>
                                    {section.children.map((child) => {
                                        const ChildIcon = child.icon;
                                        const childActive = isRouteActive(child);
                                        return (
                                            <Link key={child.label} href={route(child.route, child.params || {})} onClick={onNavigate} className={`flex min-h-9 items-center gap-2 rounded-md px-3 py-1.5 text-sm ${childActive ? `${theme.sidebarChild} font-medium` : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-950'}`}>
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
                {auth.user?.role === 'admin' ? <Link href={route('settings.index')} onClick={onNavigate} className={`mb-1 flex h-10 items-center gap-2 rounded-md px-3 text-base ${route().current('settings.*') ? 'bg-zinc-100 font-medium text-zinc-950' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950'}`}>
                    <Settings className="h-4 w-4" />
                    Paramètres
                </Link> : null}
                <Button variant="ghost" className="w-full justify-start" onClick={() => { onNavigate?.(); router.post(route('logout')); }}>
                    <LogOut className="h-4 w-4" />
                    Déconnexion
                </Button>
            </div>
        </aside>
    );
}
