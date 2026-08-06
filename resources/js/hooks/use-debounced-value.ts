import { useEffect, useState } from 'react';

/**
 * Returns `value` only after it has stopped changing for `delay` ms.
 *
 * Used so typing in a search box does not fire a request per keystroke — the
 * club's lists are small, but the server round trip is not free and the
 * flicker is distracting.
 */
export function useDebouncedValue<T>(value: T, delay = 300): T {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(value), delay);

        return () => clearTimeout(timer);
    }, [value, delay]);

    return debounced;
}
