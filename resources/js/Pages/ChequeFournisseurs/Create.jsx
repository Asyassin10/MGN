import PartyChequeForm from '@/Components/PartyChequeForm';

export default function Create({ fournisseurs, banks }) {
    return <PartyChequeForm title="Nouveau chèque fournisseur" action={route('cheque-fournisseurs.store')} ownerKey="fournisseur_id" ownerLabel="Fournisseur" owners={fournisseurs} banks={banks} />;
}
