import { cn } from '@/lib/utils';

export function Card({ className, ...props }) {
    return <div className={cn('rounded-md border border-zinc-200 bg-white', className)} {...props} />;
}

export function CardHeader({ className, ...props }) {
    return <div className={cn('border-b border-zinc-200 p-4', className)} {...props} />;
}

export function CardTitle({ className, ...props }) {
    return <h2 className={cn('text-sm font-semibold text-zinc-950', className)} {...props} />;
}

export function CardContent({ className, ...props }) {
    return <div className={cn('p-4', className)} {...props} />;
}
