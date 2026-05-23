import { cn } from '@/lib/utils';

export function Badge({ className, variant = 'default', ...props }) {
    const variants = {
        default: 'border-zinc-300 bg-zinc-50 text-zinc-800',
        green: 'border-green-300 bg-green-50 text-green-700',
        red: 'border-red-300 bg-red-50 text-red-700',
        yellow: 'border-yellow-300 bg-yellow-50 text-yellow-800',
    };

    return <span className={cn('inline-flex rounded-md border px-2 py-0.5 text-xs font-medium', variants[variant], className)} {...props} />;
}
