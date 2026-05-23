import ChequeForm from '@/Components/ChequeForm';

export default function Edit({ cheque, tiers, banques }) {
    return <ChequeForm title={`Modifier ${cheque.numero_cheque}`} action={route('cheques.update', cheque.id)} method="patch" cheque={cheque} tiers={tiers} banques={banques} />;
}
