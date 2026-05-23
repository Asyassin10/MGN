import PartyChequeIndex from '@/Components/PartyChequeIndex';

export default function Index({ cheques, filters, fournisseurs, banques }) {
    return <PartyChequeIndex title="Chèques fournisseurs" routeName="cheque-fournisseurs" ownerKey="fournisseur_id" ownerLabel="Fournisseur" ownerColumn="fournisseur" owners={fournisseurs} cheques={cheques} filters={filters} banques={banques} />;
}
