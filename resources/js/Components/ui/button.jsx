import { cn } from '@/lib/utils';

export function Button({ className, variant = 'default', size = 'default', ...props }) {
    const variants = {
        default: 'bg-zinc-900 text-white hover:bg-zinc-800',
        outline: 'border border-zinc-300 bg-white text-zinc-900 hover:bg-zinc-50',
        ghost: 'text-zinc-700 hover:bg-zinc-100',
        destructive: 'bg-red-600 text-white hover:bg-red-700',
    };
    const sizes = {
        default: 'h-10 px-4 text-base',
        sm: 'h-9 px-3 text-sm',
        lg: 'h-11 px-5 text-base',
        icon: 'h-9 w-9 p-0',
    };

    return (
        <button
            className={cn('inline-flex items-center justify-center gap-2 rounded-md font-medium transition disabled:pointer-events-none disabled:opacity-50', variants[variant], sizes[size], className)}
            {...props}
        />
    );
}
