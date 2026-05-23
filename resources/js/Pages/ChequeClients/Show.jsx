import PartyChequeShow from '@/Components/PartyChequeShow';

export default function Show({ cheque }) {
    return <PartyChequeShow title={`Chèque client ${cheque.numero_cheque}`} routeName="cheque-clients" ownerLabel="Client" ownerValue={cheque.client} cheque={cheque} />;
}
