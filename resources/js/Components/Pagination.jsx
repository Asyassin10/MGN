import { Link } from '@inertiajs/react';

export default function Pagination({ links = [] }) {
    if (!links.length) return null;

    return (
        <div className="mt-4 flex flex-wrap gap-1">
            {links.map((link, index) => (
                <Link
                    key={`${link.label}-${index}`}
                    href={link.url || '#'}
                    preserveScroll
                    className={`rounded-md border px-3 py-1.5 text-sm ${link.active ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-200 bg-white text-zinc-700'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}
