import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';

const types = [{ value: 'entree', label: 'Entrée' }, { value: 'sortie', label: 'Sortie' }];

export default function Create({ depots, employees, articles, operation = null }) {
    const editing = Boolean(operation);
    const initial = operation || { type: 'entree', depot_id: depots[0]?.value || '', employee_id: '', note: '', lines: [] };
    const { data, setData, post, patch, processing, errors } = useForm(initial);
    const [line, setLine] = useState({ article_id: '', quantity: 1 });
    const addLine = () => {
        if (!line.article_id) return;
        setData('lines', [...data.lines, { ...line, quantity: Number(line.quantity || 1) }]);
        setLine({ article_id: '', quantity: 1 });
    };
    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            patch(route('operations.update', operation.id));
            return;
        }
        post(route('operations.store'));
    };

    return (
        <AppLayout title={editing ? `Modifier ${operation.reference}` : 'Nouvelle opération'}>
            <Card className="max-w-4xl"><CardContent>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-3 md:grid-cols-3"><SearchableSelect value={data.type} onChange={(v) => setData('type', v)} options={types} allowEmpty={false} /><SearchableSelect value={data.depot_id} onChange={(v) => setData('depot_id', v)} options={depots} allowEmpty={false} /><SearchableSelect value={data.employee_id} onChange={(v) => setData('employee_id', v)} options={employees} placeholder="Employé" /></div>
                    <Textarea placeholder="Note" value={data.note} onChange={(e) => setData('note', e.target.value)} />
                    <div className="grid gap-2 md:grid-cols-[1fr_120px_auto]"><SearchableSelect value={line.article_id} onChange={(v) => setLine({ ...line, article_id: v })} options={articles} placeholder="Article" /><Input type="number" min="1" value={line.quantity} onChange={(e) => setLine({ ...line, quantity: e.target.value })} /><Button type="button" onClick={addLine}><Plus className="h-4 w-4" />Ajouter</Button></div>
                    {errors.lines || errors.operation ? <div className="text-sm text-red-600">{errors.lines || errors.operation}</div> : null}
                    <div className="rounded-md border border-zinc-200">{data.lines.map((item, index) => <div key={`${item.article_id}-${index}`} className="flex flex-col gap-2 border-b border-zinc-100 px-3 py-2 text-sm last:border-b-0 sm:flex-row sm:items-center sm:justify-between"><span className="min-w-0 truncate">{articles.find((a) => a.value === String(item.article_id))?.label}</span><div className="flex items-center justify-between gap-3 sm:justify-end"><span>{item.quantity}</span><Button type="button" size="icon" variant="ghost" onClick={() => setData('lines', data.lines.filter((_, i) => i !== index))}><Trash2 className="h-4 w-4" /></Button></div></div>)}</div>
                    <div><Button disabled={processing}>Enregistrer</Button></div>
                </form>
            </CardContent></Card>
        </AppLayout>
    );
}
