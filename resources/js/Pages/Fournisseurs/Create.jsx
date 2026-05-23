import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({ nom: '', telephone: '', ville: '', note: '' });
    const submit = (e) => { e.preventDefault(); post(route('fournisseurs.store')); };

    return (
        <AppLayout
            title="Nouveau fournisseur"
            actions={<Link href={route('fournisseurs.index')}><Button variant="outline"><ArrowLeft className="h-4 w-4" />Retour aux fournisseurs</Button></Link>}
        >
            <Card className="max-w-2xl">
                <CardContent>
                    <p className="mb-4 text-sm text-zinc-600">Créez la fiche fournisseur. Les relevés compte pourront être ajoutés ensuite, si nécessaire.</p>
                    <form onSubmit={submit} className="grid gap-3">
                        {['nom', 'telephone', 'ville'].map((field) => (
                            <label key={field} className="grid gap-1 text-sm">
                                <span className="font-medium capitalize">{field}</span>
                                <Input value={data[field]} onChange={(e) => setData(field, e.target.value)} />
                                {errors[field] && <span className="text-xs text-red-600">{errors[field]}</span>}
                            </label>
                        ))}
                        <label className="grid gap-1 text-sm">
                            <span className="font-medium">Note</span>
                            <Textarea value={data.note} onChange={(e) => setData('note', e.target.value)} />
                        </label>
                        <div className="flex flex-wrap gap-2">
                            <Button disabled={processing}>Créer le fournisseur</Button>
                            <Link href={route('fournisseurs.index')}><Button type="button" variant="outline">Annuler</Button></Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
