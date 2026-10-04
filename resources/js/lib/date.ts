export function formatClubDate(value: string): string {
    const date = new Date(`${value.slice(0, 10)}T12:00:00Z`);

    if (Number.isNaN(date.getTime())) {
        return 'Date unavailable';
    }

    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'Africa/Kampala',
    }).format(date);
}

export function formatClubDateTime(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return 'Date unavailable';
    }

    return `${new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Africa/Kampala',
    }).format(date)} EAT`;
}
