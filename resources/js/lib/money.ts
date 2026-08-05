/**
 * Club amounts are stored as whole UGX (no minor units).
 */
export function formatUgx(amount: number): string {
    return `UGX ${amount.toLocaleString('en-UG')}`;
}
