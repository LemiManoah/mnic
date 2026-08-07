import { Link } from '@inertiajs/react';
import { type Icon } from '@tabler/icons-react';
import * as React from 'react';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

export function NavSecondary({
    items,
    label = 'Administration',
    ...props
}: {
    items: {
        title: string;
        url: string;
        icon: Icon;
    }[];
    label?: string;
} & React.ComponentPropsWithoutRef<typeof SidebarGroup>) {
    return (
        <SidebarGroup {...props}>
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton asChild>
                                <Link href={item.url} prefetch>
                                    <item.icon />
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
