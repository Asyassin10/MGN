import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import SearchableSelect from '@/Components/SearchableSelect';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { clientPriceTypeOptions } from '@/lib/articleFields';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({ nom: '', telephone: '', ville: '', price_type: 'detail', note: '' });
    const submit = (e) => { e.preventDefault(); post(route('clients.store')); };
    return <AppLayout title="Nouveau client"><Card className="max-w-2xl"><CardContent><form onSubmit={submit} className="grid gap-3">{['nom', 'telephone', 'ville'].map((field) => <label key={field} className="grid gap-1 text-base"><span className="font-medium capitalize">{field}</span><Input value={data[field]} onChange={(e) => setData(field, e.target.value)} />{errors[field] && <span className="text-sm text-red-600">{errors[field]}</span>}</label>)}<label className="grid gap-1 text-base"><span className="font-medium">Price type (نوع السعر)</span><SearchableSelect value={data.price_type} onChange={(value) => setData('price_type', value)} options={clientPriceTypeOptions} placeholder="Type de prix" allowEmpty={false} />{errors.price_type && <span className="text-sm text-red-600">{errors.price_type}</span>}</label><label className="grid gap-1 text-base"><span className="font-medium">Note</span><Textarea value={data.note} onChange={(e) => setData('note', e.target.value)} /></label><div><Button disabled={processing}>Créer</Button></div></form></CardContent></Card></AppLayout>;
}
