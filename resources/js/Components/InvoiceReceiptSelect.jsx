import { cn } from '@/lib/utils';

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
        <select
            aria-label={label}
            value={selectedValue}
            onChange={(event) => onChange(event.target.value === '1')}
            className={cn('h-10 min-w-36 rounded-md border border-zinc-300 bg-white px-3 text-base font-medium outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100', className)}
        >
            <option value="" disabled>--</option>
            {options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
        </select>
    );
}
