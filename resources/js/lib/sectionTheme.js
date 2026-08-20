const SECTION_THEMES = {
    depot: {
        key: 'depot',
        title: 'text-emerald-800',
        header: 'border-emerald-300',
        content: 'border-emerald-500',
        sidebarGroup: 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 hover:text-emerald-900',
        sidebarChild: 'bg-emerald-100 text-emerald-950 hover:bg-emerald-100 hover:text-emerald-950',
        sidebarDivider: 'border-emerald-200',
    },
    fournisseurs: {
        key: 'fournisseurs',
        title: 'text-amber-800',
        header: 'border-amber-300',
        content: 'border-amber-500',
        sidebarGroup: 'bg-amber-50 text-amber-800 hover:bg-amber-100 hover:text-amber-900',
        sidebarChild: 'bg-amber-100 text-amber-950 hover:bg-amber-100 hover:text-amber-950',
        sidebarDivider: 'border-amber-200',
    },
    clients: {
        key: 'clients',
        title: 'text-blue-800',
        header: 'border-blue-300',
        content: 'border-blue-500',
        sidebarGroup: 'bg-blue-50 text-blue-800 hover:bg-blue-100 hover:text-blue-900',
        sidebarChild: 'bg-blue-100 text-blue-950 hover:bg-blue-100 hover:text-blue-950',
        sidebarDivider: 'border-blue-200',
    },
    employees: {
        key: 'employees',
        title: 'text-violet-800',
        header: 'border-violet-300',
        content: 'border-violet-500',
        sidebarGroup: 'bg-violet-50 text-violet-800 hover:bg-violet-100 hover:text-violet-900',
        sidebarChild: 'bg-violet-100 text-violet-950 hover:bg-violet-100 hover:text-violet-950',
        sidebarDivider: 'border-violet-200',
    },
    cheques: {
        key: 'cheques',
        title: 'text-fuchsia-800',
        header: 'border-fuchsia-300',
        content: 'border-fuchsia-500',
        sidebarGroup: 'bg-fuchsia-50 text-fuchsia-800 hover:bg-fuchsia-100 hover:text-fuchsia-900',
        sidebarChild: 'bg-fuchsia-100 text-fuchsia-950 hover:bg-fuchsia-100 hover:text-fuchsia-950',
        sidebarDivider: 'border-fuchsia-200',
    },
};

const ROUTE_PREFIXES = {
    depot: ['depots.', 'articles.', 'operations.'],
    employees: ['employees.'],
    fournisseurs: ['fournisseurs.'],
    clients: ['clients.'],
    cheques: ['cheques.'],
};

export function getSectionThemeByKey(key) {
    return key ? SECTION_THEMES[key] || null : null;
}

export function getSectionTheme(routeName) {
    const key = Object.entries(ROUTE_PREFIXES).find(([, prefixes]) => prefixes.some((prefix) => routeName?.startsWith(prefix)))?.[0];
    return getSectionThemeByKey(key);
}
