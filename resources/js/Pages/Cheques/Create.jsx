import ChequeForm from '@/Components/ChequeForm';

export default function Create({ tiers, banques }) {
    return <ChequeForm title="Nouveau chèque" action={route('cheques.store')} tiers={tiers} banques={banques} />;
}
