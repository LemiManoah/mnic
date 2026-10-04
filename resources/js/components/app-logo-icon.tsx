import { usePage } from '@inertiajs/react';
import type { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function AppLogoIcon({
    className,
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    const { tenant } = usePage().props;

    return (
        <img
            src={tenant.logo_url || '/musuwa_logo.jpeg'}
            alt={`${tenant.name} logo`}
            className={cn('object-contain', className)}
            {...props}
        />
    );
}
