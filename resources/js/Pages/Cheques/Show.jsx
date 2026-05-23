import { Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import DeleteButton from '@/Components/DeleteButton';
import StatusBadge from '@/Components/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { money } from '@/lib/utils';

export default function Show({ cheque }) {
    return (
        <AppLayout title={`Chèque ${cheque.numero_cheque}`} actions={<><a href={route('cheques.pdf', cheque.id)} target="_blank" rel="noopener noreferrer"><Button variant="outline"><FileText className="h-4 w-4" />Voir PDF</Button></a><Link href={route('cheques.edit', cheque.id)}><Button>Modifier</Button></Link><DeleteButton action={route('cheques.destroy', cheque.id)} title={`Supprimer le chèque ${cheque.numero_cheque} ?`} /></>}>
            <div className="grid gap-4 md:grid-cols-3">
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Tier</div><div className="mt-2 font-medium">{cheque.tier || '-'}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Banque</div><div className="mt-2 font-medium">{cheque.banque}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Statut</div><div className="mt-2"><StatusBadge statut={cheque.statut} /></div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Montant</div><div className="mt-2 text-xl font-semibold">{money(cheque.montant)}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Émission</div><div className="mt-2 font-medium">{cheque.date_emission || '-'}</div></CardContent></Card>
                <Card><CardContent><div className="text-xs uppercase text-zinc-500">Échéance</div><div className="mt-2 font-medium">{cheque.date_echeance || '-'}</div></CardContent></Card>
            </div>
        </AppLayout>
    );
}
