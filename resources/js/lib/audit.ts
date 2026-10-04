import type { AuditLog } from '@/types';

export function readableAuditLabel(value: string): string {
    const words = value
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/[._-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

    return words.charAt(0).toUpperCase() + words.slice(1);
}

export function auditRecordType(log: AuditLog): string {
    return readableAuditLabel(log.auditable_type.split('\\').pop() ?? 'Record');
}

export function auditRecordName(log: AuditLog): string {
    for (const snapshot of [log.after, log.before]) {
        if (!snapshot) continue;

        for (const key of ['title', 'full_name', 'reference', 'label', 'name', 'purpose']) {
            const value = snapshot[key];

            if (typeof value === 'string' && value.trim() !== '') {
                return value;
            }
        }

        if (typeof snapshot.year === 'number' && typeof snapshot.month === 'number') {
            return `${snapshot.year}-${String(snapshot.month).padStart(2, '0')}`;
        }
    }

    return `${auditRecordType(log)} · ${log.auditable_id.slice(-8)}`;
}
