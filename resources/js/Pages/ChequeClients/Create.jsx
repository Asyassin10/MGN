import PartyChequeForm from '@/Components/PartyChequeForm';

export default function Create({ clients, banks }) {
    return <PartyChequeForm title="Nouveau chèque client" action={route('cheque-clients.store')} ownerKey="client_id" ownerLabel="Client" owners={clients} banks={banks} />;
}
