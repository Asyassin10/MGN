import SearchableSelect from '@/Components/SearchableSelect';

const options = [
    { value: '1', label: 'Oui' },
    { value: '0', label: 'Non' },
];

export function invoiceReceiptLabel(value) {
    return value === null || value === undefined ? '--' : value ? 'Oui' : 'Non';
}

export default function InvoiceReceiptSelect({ value, onChange, className, label = 'Facture reçue' }) {
    const selectedValue = value === null || value === undefined ? '' : value ? '1' : '0';

    return (
        <div className={className} aria-label={label}>
            <SearchableSelect
                value={selectedValue}
                onChange={(next) => onChange(next === '' ? null : next === '1')}
                options={options}
                placeholder={label}
                emptyLabel="--"
            />
        </div>
    );
}
