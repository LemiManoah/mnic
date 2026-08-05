import type { PropsWithChildren } from 'react';

/**
 * Thin page container for the administration screens.
 *
 * Navigation lives in the app sidebar (see components/app-sidebar.tsx), so
 * this layout only handles page width and spacing.
 */
export default function AdminLayout({ children }: PropsWithChildren) {
    return (
        <div className="px-4 py-6">
            <section className="mx-auto w-full max-w-5xl space-y-6">
                {children}
            </section>
        </div>
    );
}
