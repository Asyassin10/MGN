import { useEffect, useState } from 'react';

// Row selection that survives page refreshes and re-renders (kept in this browser tab's sessionStorage).
export default function usePersistentSelection(key) {
    const storageKey = 'row-selection:' + key;
    const [rows, setRows] = useState(() => {
        try {
            return JSON.parse(window.sessionStorage.getItem(storageKey)) || {};
        } catch {
            return {};
        }
    });

    useEffect(() => {
        try {
            if (Object.keys(rows).length) window.sessionStorage.setItem(storageKey, JSON.stringify(rows));
            else window.sessionStorage.removeItem(storageKey);
        } catch {
            // storage unavailable: the selection simply stays in memory
        }
    }, [rows, storageKey]);

    return [rows, setRows];
}
