import { cn } from '@/lib/utils';

export function Input({ className, ...props }) {
    return <input className={cn('h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-base outline-none focus:border-zinc-900', className)} {...props} />;
}
