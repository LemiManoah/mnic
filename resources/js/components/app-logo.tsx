import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { tenant } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-10 items-center justify-center overflow-hidden rounded-md">
                <AppLogoIcon className="size-10" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {tenant.name}
                </span>
            </div>
        </>
    );
}
