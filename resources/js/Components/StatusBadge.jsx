import { Badge } from '@/Components/ui/badge';

export default function StatusBadge({ statut }) {
    const variant = ['encaisse', 'en_caisse'].includes(statut) ? 'green' : statut === 'impaye' ? 'red' : 'yellow';
    return <Badge variant={variant}>{statut}</Badge>;
}
