import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ pin: '' });

    const submit = (event) => {
        event.preventDefault();
        post(route('login'));
    };

    return (
        <>
            <Head title="Connexion" />
            <main className="flex min-h-screen items-center justify-center bg-zinc-50 p-4">
                <Card className="w-full max-w-sm">
                    <CardHeader>
                        <div className="mb-4 flex justify-center">
                            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-950 text-xl font-bold tracking-tight text-white">DP</div>
                        </div>
                        <CardTitle className="text-center">Droguerie P</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-3">
                            <label className="grid gap-1 text-sm">
                                <span className="font-medium text-zinc-700">PIN administrateur</span>
                                <Input value={data.pin} onChange={(event) => setData('pin', event.target.value)} maxLength={6} inputMode="numeric" type="password" autoFocus />
                                {errors.pin ? <span className="text-xs text-red-600">{errors.pin}</span> : null}
                            </label>
                            <Button disabled={processing}>Connexion</Button>
                        </form>
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
