import type { Auth } from '@/types/auth';
import type { FlashToast } from '@/types/ui';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        flashDataType: {
            toast?: FlashToast;
        };
        sharedPageProps: {
            name: string;
            tenant: {
                name: string;
                logo_url: string | null;
            };
            auth: Auth;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
