import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

export default function Index() {
    const { data, setData, patch, processing, errors, reset } = useForm({
        current_pin: '',
        pin: '',
        pin_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();
        patch(route('settings.pin.update'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <AppLayout title="Paramètres">
            <Card className="max-w-xl">
                <CardHeader>
                    <CardTitle>Mettre à jour le PIN</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-4">
                        <label className="grid gap-1 text-sm">
                            <span className="font-medium text-zinc-700">PIN actuel</span>
                            <Input value={data.current_pin} onChange={(event) => setData('current_pin', event.target.value)} maxLength={6} inputMode="numeric" type="password" />
                            {errors.current_pin ? <span className="text-xs text-red-600">{errors.current_pin}</span> : null}
                        </label>
                        <label className="grid gap-1 text-sm">
                            <span className="font-medium text-zinc-700">Nouveau PIN</span>
                            <Input value={data.pin} onChange={(event) => setData('pin', event.target.value)} maxLength={6} inputMode="numeric" type="password" />
                            {errors.pin ? <span className="text-xs text-red-600">{errors.pin}</span> : null}
                        </label>
                        <label className="grid gap-1 text-sm">
                            <span className="font-medium text-zinc-700">Confirmer le nouveau PIN</span>
                            <Input value={data.pin_confirmation} onChange={(event) => setData('pin_confirmation', event.target.value)} maxLength={6} inputMode="numeric" type="password" />
                        </label>
                        <div>
                            <Button disabled={processing}>Mettre à jour</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
