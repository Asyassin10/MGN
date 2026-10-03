export const unitOptions = [{ value: 'U', label: 'U' }, { value: 'L', label: 'L' }];

export const priceTypeOptions = [
    { value: 'detail', label: 'Retail price (سعر التقسيط)', field: 'prix_detail_ttc' },
    { value: 'demi_gros', label: 'Semi-wholesale price (سعر نصف الجملة)', field: 'prix_demi_gros_ttc' },
    { value: 'gros', label: 'Wholesale price (سعر الجملة)', field: 'prix_gros_ttc' },
    { value: 'special', label: 'Special price (سعر خاص)', field: 'prix_special_ttc' },
    { value: 'min', label: 'Min price (السعر الأدنى)', field: 'prix_min' },
    { value: 'max', label: 'Max price (السعر الأقصى)', field: 'prix_max' },
];

export function articleFields(groups) {
    const num = (name, label) => ({ name, label, type: 'number' });

    return [
        { name: 'reference', label: 'Code (الكود)', section: 'Article (السلعة)' },
        { name: 'name', label: 'Article name (إسم السلعة)' },
        { name: 'nom_fournisseur', label: 'Supplier name (الإسم عند المزود)' },
        { name: 'group_id', label: 'Group (العائلة)', type: 'select', options: groups || [] },
        { name: 'unite', label: 'Unit (الوحدة)', type: 'select', options: unitOptions },
        num('commission_vendeur', 'Seller commission % (عمولة البائع)'),
        num('stock_minimum', 'Minimum stock (المخزون الأدنى)'),
        num('poids', 'Weight (وزن السلعة)'),
        { ...num('prix_achat_ttc', 'Purchase price TTC (سعر الشراء TTC)'), section: 'Purchase (الشراء)' },
        { ...num('prix_detail_ttc', 'Retail price TTC (سعر التقسيط TTC)'), section: 'Selling prices (أسعار البيع)' },
        num('prix_demi_gros_ttc', 'Semi-wholesale price TTC (سعر نصف الجملة TTC)'),
        num('prix_gros_ttc', 'Wholesale price TTC (سعر الجملة TTC)'),
        num('prix_special_ttc', 'Special price TTC (سعر خاص TTC)'),
        num('prix_min', 'Min price (السعر الأدنى)'),
        num('prix_max', 'Max price (السعر الأقصى)'),
    ];
}

export const clientPriceTypeOptions = priceTypeOptions
    .filter((option) => ['detail', 'demi_gros', 'gros', 'special'].includes(option.value))
    .map(({ value, label }) => ({ value, label }));
