import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { money } from '@/lib/utils';

const types = { salary: 'Salaire', advance: 'Avance', bonus: 'Prime / bonus' };

export default function PaymentHistory({ employee, payments, summary }) {
    const fields = [{ name: 'type', label: 'Type de paiement', type: 'select', options: [{ value: 'salary', label: 'Salaire mensuel' }, { value: 'advance', label: 'Avance sur salaire' }, { value: 'bonus', label: 'Prime / bonus' }] }, { name: 'month', label: 'Mois concerné', type: 'month' }, { name: 'payment_date', label: 'Date de paiement', type: 'date' }, { name: 'amount', label: 'Montant DH', type: 'number' }, { name: 'note', label: 'Note', type: 'textarea' }];
    return <AppLayout title={`Paiements — ${employee.name}`} actions={<><Link href={route('employees.show', employee.id)}><Button variant="outline"><ArrowLeft className="h-4 w-4" />Retour à l’employé</Button></Link><CrudDialog title="Payer un employé" action={route('employees.salary-payments.store', employee.id)} fields={fields} defaults={{ type: 'salary', month: '', payment_date: '', amount: '', note: '' }} trigger={<Button>Payer / Avance / Prime</Button>} /></>}><div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4"><Card><CardContent><div className="text-sm text-zinc-500">Nombre de paiements</div><div className="mt-1 text-2xl font-semibold">{summary.count}</div></CardContent></Card><Card><CardContent><div className="text-sm text-zinc-500">Total payé</div><div className="mt-1 text-2xl font-semibold">{money(summary.total)}</div></CardContent></Card><Card><CardContent><div className="text-sm text-zinc-500">Salaires</div><div className="mt-1 text-xl font-semibold">{money(summary.salary)}</div></CardContent></Card><Card><CardContent><div className="text-sm text-zinc-500">Avances + primes</div><div className="mt-1 text-xl font-semibold">{money(summary.advance + summary.bonus)}</div></CardContent></Card></div><div className="mb-4"><CrudDialog title="Payer un employé" action={route('employees.salary-payments.store', employee.id)} fields={fields} defaults={{ type: 'salary', month: '', payment_date: '', amount: '', note: '' }} trigger={<Button size="lg">+ Ajouter salaire, avance ou prime</Button>} /></div><DataTable columns={[{ key: 'month', label: 'Mois' }, { key: 'type', label: 'Type', render: (row) => types[row.type] }, { key: 'payment_date', label: 'Date de paiement' }, { key: 'amount', label: 'Montant DH', render: (row) => money(row.amount) }, { key: 'note', label: 'Note' }]} rows={payments} pagination={{ links: [] }} empty="Aucun paiement." /></AppLayout>;
}
