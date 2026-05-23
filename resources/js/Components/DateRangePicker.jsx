import * as Popover from '@radix-ui/react-popover';
import { CalendarDays } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

export default function DateRangePicker({ from, to, onChange, label = 'Période' }) {
    const value = from || to ? `${from || 'Début'} - ${to || 'Fin'}` : label;

    return (
        <Popover.Root>
            <Popover.Trigger asChild>
                <Button type="button" variant="outline" className="w-full justify-start">
                    <CalendarDays className="h-4 w-4" />
                    {value}
                </Button>
            </Popover.Trigger>
            <Popover.Portal>
                <Popover.Content align="start" className="z-50 mt-2 w-[min(30rem,calc(100vw-2rem))] rounded-md border border-zinc-200 bg-white p-3">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <label className="grid gap-1 text-sm">
                            <span className="font-medium text-zinc-700">Du</span>
                            <Input type="date" value={from || ''} onChange={(event) => onChange({ from: event.target.value, to })} />
                        </label>
                        <label className="grid gap-1 text-sm">
                            <span className="font-medium text-zinc-700">Au</span>
                            <Input type="date" value={to || ''} onChange={(event) => onChange({ from, to: event.target.value })} />
                        </label>
                    </div>
                    <div className="mt-3 flex justify-end">
                        <Button type="button" variant="outline" onClick={() => onChange({ from: '', to: '' })}>Effacer</Button>
                    </div>
                </Popover.Content>
            </Popover.Portal>
        </Popover.Root>
    );
}
