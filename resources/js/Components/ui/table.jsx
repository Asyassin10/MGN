import { cn } from '@/lib/utils';

export function Table({ className, ...props }) {
    return <table className={cn('w-full caption-bottom text-sm', className)} {...props} />;
}

export function TableHeader(props) {
    return <thead className="border-b border-zinc-200" {...props} />;
}

export function TableBody(props) {
    return <tbody className="divide-y divide-zinc-100" {...props} />;
}

export function TableRow({ className, ...props }) {
    return <tr className={cn('hover:bg-zinc-50', className)} {...props} />;
}

export function TableHead({ className, ...props }) {
    return <th className={cn('h-9 px-3 text-left text-xs font-medium uppercase text-zinc-500', className)} {...props} />;
}

export function TableCell({ className, ...props }) {
    return <td className={cn('px-3 py-2 text-zinc-800', className)} {...props} />;
}
