import { Link } from '@inertiajs/react';
import { type Icon } from '@tabler/icons-react';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';

export function NavMain({
    items,
    label = 'Operations',
}: {
    items: {
        title: string;
        url: string;
        icon?: Icon;
        /** Unread count shown on the right of the item; hidden when zero. */
        badge?: number;
    }[];
    label?: string;
}) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup>
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarGroupContent className="flex flex-col gap-2">
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                tooltip={item.title}
                                isActive={isCurrentOrParentUrl(item.url)}
                            >
                                <Link href={item.url} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                    {item.badge !== undefined &&
                                        item.badge > 0 && (
                                            <span className="ml-auto rounded-full bg-primary px-2 py-0.5 text-xs font-medium text-primary-foreground">
                                                {item.badge > 99
                                                    ? '99+'
                                                    : item.badge}
                                            </span>
                                        )}
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
