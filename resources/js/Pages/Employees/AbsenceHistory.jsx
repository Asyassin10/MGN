import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import CrudDialog from '@/Components/CrudDialog';
import DataTable from '@/Components/DataTable';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';

const fields = [{ name: 'absence_date', label: 'Date', type: 'date' }, { name: 'status', label: 'Statut', type: 'select', options: [{ value: 'justified', label: 'Justifiée' }, { value: 'unjustified', label: 'Non justifiée' }, { value: 'leave', label: 'Congé' }] }, { name: 'note', label: 'Note', type: 'textarea' }];

export default function AbsenceHistory({ employee, absences, count }) {
    return <AppLayout title={`Absences — ${employee.name}`} actions={<><Link href={route('employees.show', employee.id)}><Button variant="outline"><ArrowLeft className="h-4 w-4" />Retour à l’employé</Button></Link><CrudDialog title="Ajouter une absence" action={route('employees.absences.store', employee.id)} fields={fields} defaults={{ absence_date: '', status: 'unjustified', note: '' }} trigger={<Button>Absence</Button>} /></>}><div className="mb-5 max-w-xs"><Card><CardContent><div className="text-sm text-zinc-500">Total absences</div><div className="mt-1 text-2xl font-semibold">{count}</div></CardContent></Card></div><div className="mb-4"><CrudDialog title="Ajouter une absence" action={route('employees.absences.store', employee.id)} fields={fields} defaults={{ absence_date: '', status: 'unjustified', note: '' }} trigger={<Button size="lg">+ Ajouter absence</Button>} /></div><DataTable columns={[{ key: 'absence_date', label: 'Date' }, { key: 'status', label: 'Statut' }, { key: 'note', label: 'Note' }]} rows={absences} pagination={{ links: [] }} empty="Aucune absence." /></AppLayout>;
}
