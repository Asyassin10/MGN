import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({ nom: '', telephone: '', ville: '', note: '' });
    const submit = (e) => { e.preventDefault(); post(route('clients.store')); };
    return <AppLayout title="Nouveau client"><Card className="max-w-2xl"><CardContent><form onSubmit={submit} className="grid gap-3">{['nom', 'telephone', 'ville'].map((field) => <label key={field} className="grid gap-1 text-sm"><span className="font-medium capitalize">{field}</span><Input value={data[field]} onChange={(e) => setData(field, e.target.value)} />{errors[field] && <span className="text-xs text-red-600">{errors[field]}</span>}</label>)}<label className="grid gap-1 text-sm"><span className="font-medium">Note</span><Textarea value={data.note} onChange={(e) => setData('note', e.target.value)} /></label><div><Button disabled={processing}>Créer</Button></div></form></CardContent></Card></AppLayout>;
}
