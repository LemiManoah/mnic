import { Link, usePage } from '@inertiajs/react';
import { GalleryVerticalEnd } from 'lucide-react';
import type * as React from 'react';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { dashboard } from '@/routes';
import { index as actionItemIndex } from '@/routes/action-item';
import { index as auditLogIndex } from '@/routes/audit-log';
import { index as contributionPeriodIndex } from '@/routes/contribution-period';
import { index as expenseIndex } from '@/routes/expense';
import { index as externalAccountIndex } from '@/routes/external-account';
import { index as meetingIndex } from '@/routes/meeting';
import { index as memberIndex } from '@/routes/member';
import { index as monthlyReportIndex } from '@/routes/monthly-report';
import { index as paymentIndex } from '@/routes/payment';
import { index as proposalIndex } from '@/routes/proposal';
import { index as reconciliationIndex } from '@/routes/reconciliation';
import { index as settingIndex } from '@/routes/setting';
import type { NavItem } from '@/types';

type NavSection = {
    title: string;
    items: NavItem[];
};

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { auth } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const isAdministrator = auth.roles.includes('administrator');
    const isSecretary = auth.roles.includes('secretary');
    const isTreasurer = auth.roles.includes('treasurer');
    const isFinancialVerifier = auth.roles.includes('financial-verifier');

    const administrationItems: NavItem[] = [
        ...(isAdministrator
            ? [
                  {
                      title: 'Club Settings',
                      href: settingIndex(),
                  } satisfies NavItem,
              ]
            : []),
        ...(isAdministrator || isSecretary
            ? [
                  {
                      title: 'Audit Log',
                      href: auditLogIndex(),
                  } satisfies NavItem,
              ]
            : []),
    ];

    const navMain: NavSection[] = [
        {
            title: 'Overview',
            items: [{ title: 'Dashboard', href: dashboard() }],
        },
        {
            title: 'Club',
            items: [{ title: 'Members', href: memberIndex() }],
        },
        {
            title: 'Contributions',
            items: [
                { title: 'Periods', href: contributionPeriodIndex() },
                { title: 'Payments', href: paymentIndex() },
            ],
        },
        {
            title: 'Governance',
            items: [
                { title: 'Meetings', href: meetingIndex() },
                { title: 'Proposals', href: proposalIndex() },
                { title: 'Actions', href: actionItemIndex() },
            ],
        },
        {
            title: 'Finance',
            items: [
                { title: 'Expenses', href: expenseIndex() },
                { title: 'Reconciliation', href: reconciliationIndex() },
                { title: 'Monthly reports', href: monthlyReportIndex() },
            ],
        },
        {
            title: 'Finance',
            items: [
                { title: 'Expenses', href: expenseIndex() },
                { title: 'Reconciliation', href: reconciliationIndex() },
                ...(isAdministrator || isTreasurer || isFinancialVerifier
                    ? [
                          {
                              title: 'External accounts',
                              href: externalAccountIndex(),
                          } satisfies NavItem,
                      ]
                    : []),
            ],
        },
        ...(administrationItems.length > 0
            ? [
                  {
                      title: 'Administration',
                      items: administrationItems,
                  } satisfies NavSection,
              ]
            : []),
    ];

    return (
        <Sidebar {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                    <GalleryVerticalEnd className="size-4" />
                                </div>
                                <div className="flex flex-col gap-0.5 leading-none">
                                    <span className="font-medium">
                                        Musuwa Nation
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        Investment Club
                                    </span>
                                </div>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarMenu>
                        {navMain.map((section) => (
                            <SidebarMenuItem key={section.title}>
                                <SidebarMenuButton asChild>
                                    <Link
                                        href={section.items[0].href}
                                        className="font-medium"
                                        prefetch
                                    >
                                        {section.title}
                                    </Link>
                                </SidebarMenuButton>

                                {section.items.length > 0 ? (
                                    <SidebarMenuSub>
                                        {section.items.map((item) => (
                                            <SidebarMenuSubItem
                                                key={item.title}
                                            >
                                                <SidebarMenuSubButton
                                                    asChild
                                                    isActive={isCurrentOrParentUrl(
                                                        item.href,
                                                    )}
                                                >
                                                    <Link
                                                        href={item.href}
                                                        prefetch
                                                    >
                                                        {item.title}
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        ))}
                                    </SidebarMenuSub>
                                ) : null}
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>
    );
}
