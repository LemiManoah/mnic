import { router } from '@inertiajs/react';
import { IconSearch, IconX } from '@tabler/icons-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import type { Option } from '@/types';

export type SelectFilter = {
    /** Query string key, e.g. "status". */
    name: string;
    label: string;
    value: string | null;
    options: Option[];
};

/**
 * Search box plus optional dropdown filters for an index page.
 *
 * Everything is driven through the query string, so a filtered list can be
 * bookmarked or pasted to another member and they see the same thing. Requests
 * preserve scroll and state, so the page does not jump while you type.
 */
export default function ListFilters({
    url,
    search,
    placeholder = 'Search…',
    filters = [],
}: {
    /** The index route this list lives on. */
    url: string;
    /**
     * Current search term, or omit entirely on lists the backend does not
     * search — the box is hidden rather than shown doing nothing.
     */
    search?: string | null;
    placeholder?: string;
    filters?: SelectFilter[];
}) {
    const [term, setTerm] = useState(search ?? '');
    const debouncedTerm = useDebouncedValue(term);

    // Skip the first run, otherwise simply opening the page fires a request
    // that re-sends the filters already in the URL.
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        if ((search ?? '') === debouncedTerm) {
            return;
        }

        router.get(
            url,
            {
                ...currentFilterValues(filters),
                search: debouncedTerm === '' ? undefined : debouncedTerm,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
        // `filters` and `search` are re-created each render; reacting to them
        // here would loop. The debounced term is the only real trigger.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debouncedTerm]);

    const applyFilter = (name: string, value: string) => {
        router.get(
            url,
            {
                ...currentFilterValues(filters),
                [name]: value === '' ? undefined : value,
                search: term === '' ? undefined : term,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const clearAll = () => {
        setTerm('');
        router.get(
            url,
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasActiveFilter =
        term !== '' || filters.some((filter) => filter.value);

    return (
        <div className="flex flex-wrap items-end gap-3">
            {search !== undefined && (
                <div className="relative min-w-56 flex-1">
                    <IconSearch className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                        placeholder={placeholder}
                        aria-label={placeholder}
                        className="pl-9"
                    />
                </div>
            )}

            {filters.map((filter) => (
                <div key={filter.name} className="grid gap-1">
                    <label
                        htmlFor={`filter-${filter.name}`}
                        className="text-xs text-muted-foreground"
                    >
                        {filter.label}
                    </label>
                    <select
                        id={`filter-${filter.name}`}
                        value={filter.value ?? ''}
                        onChange={(event) =>
                            applyFilter(filter.name, event.target.value)
                        }
                        className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                    >
                        <option value="">All</option>
                        {filter.options.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>
            ))}

            {hasActiveFilter && (
                <Button variant="ghost" size="sm" onClick={clearAll}>
                    <IconX className="size-4" />
                    Clear
                </Button>
            )}
        </div>
    );
}

function currentFilterValues(filters: SelectFilter[]): Record<string, string> {
    return Object.fromEntries(
        filters
            .filter((filter) => filter.value)
            .map((filter) => [filter.name, filter.value as string]),
    );
}
