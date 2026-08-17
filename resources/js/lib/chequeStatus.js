const rowClasses = {
    en_cours: 'status-row status-row-en-cours',
    en_caisse: 'status-row status-row-en-caisse',
    encaisse: 'status-row status-row-encaisse',
    impaye: 'status-row status-row-impaye',
};

export function getChequeRowClass(row) {
    if (row.statut === 'en_caisse' && row.facture_recue && row.facture_donnee) {
        return 'status-row-complete';
    }

    return rowClasses[row.statut] || '';
}
