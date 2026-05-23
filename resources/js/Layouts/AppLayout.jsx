import { Head } from '@inertiajs/react';
import { useEffect } from 'react';
import { Menu } from 'lucide-react';
import { toast } from 'sonner';
import AppSidebar from '@/Components/AppSidebar';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/Components/ui/button';

export default function AppLayout({ title, children, actions }) {
    const { flash } = usePage().props;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-zinc-50 text-zinc-950">
                <AppSidebar className="hidden lg:flex" />
                {mobileMenuOpen ? (
                    <div className="fixed inset-0 z-40 lg:hidden">
                        <button type="button" aria-label="Fermer le menu" className="absolute inset-0 bg-zinc-950/35" onClick={() => setMobileMenuOpen(false)} />
                        <AppSidebar className="relative flex w-[min(18rem,86vw)] shadow-xl" onNavigate={() => setMobileMenuOpen(false)} />
                    </div>
                ) : null}
                <main className="min-h-screen lg:ml-64">
                    <header className="sticky top-0 z-20 flex min-h-14 flex-col gap-3 border-b border-zinc-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div className="flex items-center gap-2">
                            <Button type="button" size="icon" variant="ghost" className="lg:hidden" onClick={() => setMobileMenuOpen(true)} aria-label="Ouvrir le menu">
                                <Menu className="h-5 w-5" />
                            </Button>
                            <h1 className="text-base font-semibold">{title}</h1>
                        </div>
                        {actions ? <div className="flex w-full flex-wrap gap-2 sm:w-auto sm:justify-end">{actions}</div> : null}
                    </header>
                    <div className="p-4 sm:p-6">{children}</div>
                </main>
            </div>
        </>
    );
}
