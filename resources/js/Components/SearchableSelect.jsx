import * as Popover from '@radix-ui/react-popover';
import { Check, ChevronsUpDown, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

export default function SearchableSelect({ value, onChange, options = [], placeholder = 'Sélectionner', allowEmpty = true }) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const selected = options.find((option) => String(option.value) === String(value));
    const filtered = useMemo(() => options.filter((option) => option.label.toLowerCase().includes(search.toLowerCase())), [options, search]);

    return (
        <Popover.Root open={open} onOpenChange={setOpen}>
            <Popover.Trigger asChild>
                <Button type="button" variant="outline" className="w-full justify-between">
                    <span className="truncate">{selected?.label || placeholder}</span>
                    <ChevronsUpDown className="h-4 w-4 text-zinc-500" />
                </Button>
            </Popover.Trigger>
            <Popover.Portal>
                <Popover.Content className="z-50 w-[min(18rem,calc(100vw-2rem))] rounded-md border border-zinc-200 bg-white p-2" align="start">
                    <div className="relative mb-2">
                        <Search className="absolute left-2 top-2.5 h-4 w-4 text-zinc-400" />
                        <Input className="pl-8" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Rechercher..." />
                    </div>
                    <div className="max-h-64 overflow-auto">
                        {allowEmpty ? (
                            <button type="button" className="flex w-full items-center justify-between rounded px-2 py-2 text-left text-sm hover:bg-zinc-50" onClick={() => { onChange(''); setOpen(false); }}>
                                Tous
                            </button>
                        ) : null}
                        {filtered.map((option) => (
                            <button key={option.value} type="button" className="flex w-full items-center justify-between rounded px-2 py-2 text-left text-sm hover:bg-zinc-50" onClick={() => { onChange(option.value); setOpen(false); }}>
                                <span className="truncate">{option.label}</span>
                                {String(option.value) === String(value) ? <Check className="h-4 w-4" /> : null}
                            </button>
                        ))}
                    </div>
                </Popover.Content>
            </Popover.Portal>
        </Popover.Root>
    );
}
