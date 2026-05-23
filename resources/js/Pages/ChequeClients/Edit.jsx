import PartyChequeForm from '@/Components/PartyChequeForm';

export default function Edit({ cheque, clients, banks }) {
    return <PartyChequeForm title={`Modifier ${cheque.numero_cheque}`} action={route('cheque-clients.update', cheque.id)} method="patch" cheque={cheque} ownerKey="client_id" ownerLabel="Client" owners={clients} banks={banks} />;
}
