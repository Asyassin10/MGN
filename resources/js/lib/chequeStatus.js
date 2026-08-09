const rowClasses = {
    en_cours: 'status-row status-row-en-cours',
    en_caisse: 'status-row status-row-en-caisse',
    encaisse: 'status-row status-row-encaisse',
    impaye: 'status-row status-row-impaye',
};

export function getChequeRowClass(row) {
    if (row.facture_recue && row.facture_donnee) {
        return 'invoice-row-complete';
    }

    if (row.facture_recue) {
        return 'invoice-row-received';
    }

    if (row.facture_donnee) {
        return 'invoice-row-given';
    }

    return rowClasses[row.statut] || '';
}
