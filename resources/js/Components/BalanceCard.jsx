import { Card, CardContent } from '@/Components/ui/card';
import { money } from '@/lib/utils';

export default function BalanceCard({ label, value, positive = 'green' }) {
    const isPositive = Number(value || 0) > 0;
    const color = isPositive ? (positive === 'green' ? 'text-green-700' : 'text-red-700') : (positive === 'green' ? 'text-red-700' : 'text-green-700');

    return (
        <Card>
            <CardContent>
                <div className="text-xs font-medium uppercase text-zinc-500">{label}</div>
                <div className={`mt-2 text-xl font-semibold ${color}`}>{money(value)}</div>
            </CardContent>
        </Card>
    );
}
