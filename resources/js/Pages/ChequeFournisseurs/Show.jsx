import PartyChequeShow from '@/Components/PartyChequeShow';

export default function Show({ cheque }) {
    return <PartyChequeShow title={`Chèque fournisseur ${cheque.numero_cheque}`} routeName="cheque-fournisseurs" ownerLabel="Fournisseur" ownerValue={cheque.fournisseur} cheque={cheque} />;
}
