import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

export function Checkbox({ checked = false, onCheckedChange, className, ...props }) {
    return (
        <button
            type="button"
            role="checkbox"
            aria-checked={checked}
            onClick={() => onCheckedChange?.(!checked)}
            className={cn(
                'flex h-5 w-5 items-center justify-center rounded border border-zinc-300 bg-white text-white outline-none focus:ring-2 focus:ring-zinc-400',
                checked && 'border-zinc-950 bg-zinc-950',
                className,
            )}
            {...props}
        >
            {checked ? <Check className="h-3.5 w-3.5" /> : null}
        </button>
    );
}
