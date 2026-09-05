import { Card, CardContent } from '@/Components/ui/card';
import { money } from '@/lib/utils';

export default function BalanceCard({ label, value, positive = 'green', format = 'money' }) {
    const isPositive = Number(value || 0) > 0;
    const color = isPositive ? (positive === 'green' ? 'text-green-700' : 'text-red-700') : (positive === 'green' ? 'text-red-700' : 'text-green-700');

    return (
        <Card className="summary-card">
            <CardContent>
                <div className="summary-label">{label}</div>
                <div className={`summary-value ${color}`}>{format === 'number' ? Number(value || 0) : money(value)}</div>
            </CardContent>
        </Card>
    );
}
