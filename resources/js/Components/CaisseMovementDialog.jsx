import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Building2, UserPlus } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import SearchableSelect from '@/Components/SearchableSelect';
import CrudDialog from '@/Components/CrudDialog';

const types = [{ value: 'entree', label: 'Entrée' }, { value: 'sortie', label: 'Sortie' }];

export default function CaisseMovementDialog({ title, action, method = 'post', defaults, submitLabel = 'Enregistrer', parties, trigger, newParty }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, patch, processing, errors, reset } = useForm(defaults);

    useEffect(() => {
        if (open) Object.entries(defaults).forEach(([key, value]) => setData(key, value ?? ''));
    }, [open]);

    useEffect(() => {
        if (open && newParty) setData('party', newParty.value);
    }, [newParty]);

    const submit = (event) => {
        event.preventDefault();
        const visit = method === 'patch' ? patch : post;
        visit(action, {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader><DialogTitle>{title}</DialogTitle></DialogHeader>
                <form onSubmit={submit} className="grid gap-3">
                    <label className="grid gap-1 text-base">
                        <span className="font-medium text-zinc-700">Type</span>
                        <SearchableSelect value={data.type} onChange={(value) => setData('type', value)} options={types} allowEmpty={false} />
                    </label>
                    <label className="grid gap-1 text-base">
                        <span className="font-medium text-zinc-700">Client / Fournisseur</span>
                        <SearchableSelect value={data.party} onChange={(value) => setData('party', value)} options={parties} allowEmpty={false} placeholder="Client / Fournisseur" />
                        {errors.party ? <span className="text-sm text-red-600">{errors.party}</span> : null}
                    </label>
                    <div className="flex flex-wrap gap-2">
                        <CrudDialog
                            title="Nouveau client"
                            action={route('caisse.quick-client')}
                            fields={[{ name: 'nom', label: 'Nom' }]}
                            defaults={{ nom: '' }}
                            submitLabel="Créer"
                            preserveState
                            trigger={<Button type="button" size="sm" variant="outline"><UserPlus className="h-4 w-4" />Nouveau client</Button>}
                        />
                        <CrudDialog
                            title="Nouveau fournisseur"
                            action={route('caisse.quick-fournisseur')}
                            fields={[{ name: 'nom', label: 'Nom' }]}
                            defaults={{ nom: '' }}
                            submitLabel="Créer"
                            preserveState
                            trigger={<Button type="button" size="sm" variant="outline"><Building2 className="h-4 w-4" />Nouveau fournisseur</Button>}
                        />
                    </div>
                    <label className="grid gap-1 text-base">
                        <span className="font-medium text-zinc-700">Montant</span>
                        <Input type="number" value={data.montant} onChange={(event) => setData('montant', event.target.value)} />
                        {errors.montant ? <span className="text-sm text-red-600">{errors.montant}</span> : null}
                    </label>
                    <label className="grid gap-1 text-base">
                        <span className="font-medium text-zinc-700">Note</span>
                        <Textarea value={data.note || ''} onChange={(event) => setData('note', event.target.value)} />
                    </label>
                    <div className="mt-2 flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>Annuler</Button>
                        <Button disabled={processing}>{submitLabel}</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
