import PartyChequeIndex from '@/Components/PartyChequeIndex';

export default function Index({ cheques, filters, clients, banques }) {
    return <PartyChequeIndex title="Chèques clients" routeName="cheque-clients" ownerKey="client_id" ownerLabel="Client" ownerColumn="client" owners={clients} cheques={cheques} filters={filters} banques={banques} />;
}
