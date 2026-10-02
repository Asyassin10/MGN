export const unitOptions = [{ value: 'U', label: 'U' }, { value: 'L', label: 'L' }];

export const priceTypeOptions = [
    { value: 'detail', label: 'Retail price (سعر التقسيط)', field: 'prix_detail_ht' },
    { value: 'demi_gros', label: 'Semi-wholesale price (سعر نصف الجملة)', field: 'prix_demi_gros_ht' },
    { value: 'gros', label: 'Wholesale price (سعر الجملة)', field: 'prix_gros_ht' },
    { value: 'special', label: 'Special price (سعر خاص)', field: 'prix_special_ht' },
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
        { ...num('prix_achat_ht', 'Purchase price HT (سعر الشراء HT)'), section: 'Purchase (الشراء)' },
        num('prix_achat_ttc', 'Purchase price TTC (سعر الشراء TTC)'),
        { ...num('marge_detail', 'Retail margin % (هامش الربح التقسيط)'), section: 'Margins (هوامش الربح)' },
        num('marge_gros', 'Wholesale margin % (هامش الربح الجملة)'),
        num('marge_demi_gros', 'Semi-wholesale margin % (هامش الربح نصف الجملة)'),
        num('marge_special', 'Special margin % (هامش الربح الخاص)'),
        { ...num('prix_detail_ht', 'Retail price HT (سعر التقسيط HT)'), section: 'Selling prices (أسعار البيع)' },
        num('prix_detail_ttc', 'Retail price TTC (سعر التقسيط TTC)'),
        num('prix_demi_gros_ht', 'Semi-wholesale price HT (سعر نصف الجملة HT)'),
        num('prix_demi_gros_ttc', 'Semi-wholesale price TTC (سعر نصف الجملة TTC)'),
        num('prix_gros_ht', 'Wholesale price HT (سعر الجملة HT)'),
        num('prix_gros_ttc', 'Wholesale price TTC (سعر الجملة TTC)'),
        num('prix_special_ht', 'Special price HT (سعر خاص HT)'),
        num('prix_special_ttc', 'Special price TTC (سعر خاص TTC)'),
        num('prix_min', 'Min price (السعر الأدنى)'),
        num('prix_max', 'Max price (السعر الأقصى)'),
    ];
}
