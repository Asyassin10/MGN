import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import SearchableSelect from '@/Components/SearchableSelect';

export default function CrudDialog({ title, trigger, action, method = 'post', fields, defaults = {}, submitLabel = 'Enregistrer' }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, patch, processing, errors, reset } = useForm(defaults);

    useEffect(() => {
        if (open) Object.entries(defaults).forEach(([key, value]) => setData(key, value ?? ''));
    }, [open]);

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
                    {fields.map((field) => (
                        <label key={field.name} className="grid gap-1 text-sm">
                            <span className="font-medium text-zinc-700">{field.label}</span>
                            {field.type === 'textarea' ? (
                                <Textarea value={data[field.name] || ''} onChange={(event) => setData(field.name, event.target.value)} />
                            ) : field.type === 'select' ? (
                                <SearchableSelect value={data[field.name] || ''} onChange={(value) => setData(field.name, value)} options={field.options || []} allowEmpty={field.allowEmpty ?? false} placeholder={field.placeholder || field.label} />
                            ) : (
                                <Input type={field.type || 'text'} value={data[field.name] || ''} onChange={(event) => setData(field.name, event.target.value)} />
                            )}
                            {errors[field.name] ? <span className="text-xs text-red-600">{errors[field.name]}</span> : null}
                        </label>
                    ))}
                    <div className="mt-2 flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>Annuler</Button>
                        <Button disabled={processing}>{submitLabel}</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
