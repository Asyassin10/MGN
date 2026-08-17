import { router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';

export default function DeleteButton({ action, title = 'Supprimer cet élément ?', message = 'Cette action est définitive.', showLabel = true }) {
    const [open, setOpen] = useState(false);
    const { auth } = usePage().props;
    const module = action.includes('/fournisseurs') ? 'fournisseurs' : action.includes('/clients') ? 'clients' : action.includes('/employees') ? 'employees' : action.includes('/depots') || action.includes('/articles') || action.includes('/operations') ? 'depots' : null;

    if (module && auth.user?.role !== 'admin' && !auth.user?.permissions?.delete?.includes(module)) return null;

    const destroy = () => {
        // Release the modal overlay before Inertia navigates to a different screen.
        setOpen(false);
        router.delete(action, {
            preserveScroll: true,
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size={showLabel ? 'sm' : 'icon'} variant="ghost" className="text-red-700 hover:bg-red-50 hover:text-red-800" aria-label={showLabel ? undefined : 'Supprimer'}>
                    <Trash2 className="h-4 w-4" />
                    {showLabel ? 'Supprimer' : null}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-md">
                <DialogHeader><DialogTitle>{title}</DialogTitle></DialogHeader>
                <p className="mb-5 text-base text-zinc-600">{message}</p>
                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button type="button" variant="outline" onClick={() => setOpen(false)}>Annuler</Button>
                    <Button type="button" variant="destructive" onClick={destroy}>Confirmer la suppression</Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
