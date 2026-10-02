import { Fragment, useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import SearchableSelect from '@/Components/SearchableSelect';

export default function CrudDialog({ title, trigger, action, method = 'post', fields, defaults = {}, submitLabel = 'Enregistrer', preserveState = false, wide = false }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, patch, processing, errors, reset } = useForm(defaults);

    useEffect(() => {
        if (open) Object.entries(defaults).forEach(([key, value]) => setData(key, value ?? ''));
    }, [open]);

    const submit = (event) => {
        event.preventDefault();
        // Radix's DialogContent renders via a React Portal: DOM placement moves, but React's
        // synthetic events still bubble through the React tree. Without this, submitting a
        // CrudDialog nested inside another <form> (e.g. a quick-create dialog opened from
        // within a bigger form) would also trigger that outer form's onSubmit.
        event.stopPropagation();
        const visit = method === 'patch' ? patch : post;
        visit(action, {
            preserveScroll: true,
            preserveState,
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className={wide ? 'max-w-3xl' : undefined}>
                <DialogHeader><DialogTitle>{title}</DialogTitle></DialogHeader>
                <form onSubmit={submit} className={wide ? 'grid gap-3 sm:grid-cols-2' : 'grid gap-3'}>
                    {fields.map((field) => (
                        <Fragment key={field.name}>
                        {field.section ? <div className="mt-2 border-b border-zinc-200 pb-1 text-sm font-semibold uppercase tracking-wide text-emerald-800 sm:col-span-2">{field.section}</div> : null}
                        <label className={`grid content-start gap-1 text-base ${field.full ? 'sm:col-span-2' : ''}`}>
                            <span className="font-medium text-zinc-700">{field.label}</span>
                            {field.type === 'textarea' ? (
                                <Textarea value={data[field.name] || ''} onChange={(event) => setData(field.name, event.target.value)} />
                            ) : field.type === 'select' ? (
                                <SearchableSelect value={data[field.name] || ''} onChange={(value) => setData(field.name, value)} options={field.options || []} allowEmpty={field.allowEmpty ?? false} placeholder={field.placeholder || field.label} />
                            ) : field.type === 'checkbox' ? (
                                <Checkbox checked={Boolean(data[field.name])} onCheckedChange={(checked) => setData(field.name, checked)} />
                            ) : (
                                <Input type={field.type || 'text'} step={field.type === 'number' ? 'any' : undefined} value={data[field.name] || ''} onChange={(event) => setData(field.name, event.target.value)} />
                            )}
                            {errors[field.name] ? <span className="text-sm text-red-600">{errors[field.name]}</span> : null}
                        </label>
                        </Fragment>
                    ))}
                    <div className="mt-2 flex justify-end gap-2 sm:col-span-2">
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>Annuler</Button>
                        <Button disabled={processing}>{submitLabel}</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
