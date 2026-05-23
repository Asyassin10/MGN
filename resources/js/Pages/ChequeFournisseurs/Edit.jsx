import PartyChequeForm from '@/Components/PartyChequeForm';

export default function Edit({ cheque, fournisseurs, banks }) {
    return <PartyChequeForm title={`Modifier ${cheque.numero_cheque}`} action={route('cheque-fournisseurs.update', cheque.id)} method="patch" cheque={cheque} ownerKey="fournisseur_id" ownerLabel="Fournisseur" owners={fournisseurs} banks={banks} />;
}
