import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';

export default function DeleteButton({ action, title = 'Supprimer cet élément ?', message = 'Cette action est définitive.', showLabel = true }) {
    const [open, setOpen] = useState(false);

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
                <p className="mb-5 text-sm text-zinc-600">{message}</p>
                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button type="button" variant="outline" onClick={() => setOpen(false)}>Annuler</Button>
                    <Button type="button" variant="destructive" onClick={destroy}>Confirmer la suppression</Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
