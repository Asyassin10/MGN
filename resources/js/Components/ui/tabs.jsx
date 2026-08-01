import * as TabsPrimitive from '@radix-ui/react-tabs';
import { cn } from '@/lib/utils';

export const Tabs = TabsPrimitive.Root;

export function TabsList({ className, ...props }) {
    return <TabsPrimitive.List className={cn('flex w-full overflow-x-auto rounded-md border border-zinc-200 bg-zinc-50 p-1 sm:inline-flex sm:w-auto', className)} {...props} />;
}

export function TabsTrigger({ className, tone, value, ...props }) {
    const resolvedTone = tone || ({ factures: 'factures', payments: 'payments', entries: 'entries' }[value]);
    return <TabsPrimitive.Trigger value={value} data-tone={resolvedTone} className={cn('flex-1 whitespace-nowrap rounded px-3 py-1.5 text-base text-zinc-600 data-[state=active]:bg-white data-[state=active]:text-zinc-950 sm:flex-none', className)} {...props} />;
}

export function TabsContent({ className, tone, value, ...props }) {
    const resolvedTone = tone || ({ factures: 'factures', payments: 'payments', entries: 'entries' }[value]);
    return <TabsPrimitive.Content value={value} data-tab-panel data-tone={resolvedTone} className={cn('mt-4 border-t-2 pt-4', className)} {...props} />;
}
