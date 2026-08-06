import { Head, Link } from '@inertiajs/react';
import {
    IconAlertTriangle,
    IconCalendarEvent,
    IconCircleCheck,
    IconGavel,
} from '@tabler/icons-react';
import {
    ClubStatCards,
    type StatCard,
} from '@/components/club-stat-cards';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatUgx } from '@/lib/money';
import { dashboard } from '@/routes';
import { index as expenseIndex } from '@/routes/expense';
import { index as paymentIndex } from '@/routes/payment';
import { index as proposalIndex } from '@/routes/proposal';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

type Personal = {
    outstanding: number;
    verified_total: number;
    advance: number;
};

type Club = {
    net_position: number;
    verified_inflows: number;
    settled_outflows: number;
    reconciled_to: string | null;
};

type CurrentPeriod = {
    label: string;
    due_date: string;
    expected: number;
    collected: number;
};

type Officer = {
    payments_awaiting_verification: number;
    expenses_awaiting_approval: number;
    expenses_awaiting_verification: number;
    members_in_arrears: number;
};

type Governance = {
    open_votes: number;
    next_meeting: {
        reference: string;
        title: string;
        scheduled_for: string;
    } | null;
};

export default function Dashboard({
    personal,
    club,
    currentPeriod,
    officer,
    governance,
}: {
    personal: Personal | null;
    club: Club;
    currentPeriod: CurrentPeriod | null;
    officer: Officer;
    governance: Governance;
}) {
    const clubCards: StatCard[] = [
        {
            label: 'Club net position',
            value: formatUgx(club.net_position),
            badge: club.reconciled_to ?? 'unreconciled',
            needsAttention: club.reconciled_to === null,
            headline: club.reconciled_to
                ? `Reconciled to ${club.reconciled_to}`
                : 'Not yet reconciled',
            detail: 'Verified contributions less settled expenses',
        },
        {
            label: 'Verified inflows',
            value: formatUgx(club.verified_inflows),
            headline: 'Contributions confirmed',
            detail: 'Only payments a second officer has verified',
        },
        {
            label: 'Settled outflows',
            value: formatUgx(club.settled_outflows),
            headline: 'Expenses paid out',
            detail: 'Approved, paid and recorded against an account',
        },
        {
            label: 'Members in arrears',
            value: String(officer.members_in_arrears),
            badge: officer.members_in_arrears > 0 ? 'follow up' : 'all clear',
            needsAttention: officer.members_in_arrears > 0,
            headline:
                officer.members_in_arrears > 0
                    ? 'Outstanding obligations'
                    : 'Everyone is up to date',
            detail: 'Members with an unpaid or part-paid obligation',
        },
    ];

    const personalCards: StatCard[] = personal
        ? [
              {
                  label: 'You owe',
                  value: formatUgx(personal.outstanding),
                  badge: personal.outstanding > 0 ? 'due' : 'clear',
                  needsAttention: personal.outstanding > 0,
                  headline:
                      personal.outstanding > 0
                          ? 'Outstanding this cycle'
                          : 'Nothing outstanding',
                  detail: 'Across every open obligation',
              },
              {
                  label: 'Verified to date',
                  value: formatUgx(personal.verified_total),
                  headline: 'Your confirmed contributions',
                  detail: 'Total verified since you joined',
              },
              {
                  label: 'Unapplied advance',
                  value: formatUgx(personal.advance),
                  headline: 'Held against future months',
                  detail: 'Overpayment not yet allocated',
              },
              ...(currentPeriod
                  ? [
                        {
                            label: `Period ${currentPeriod.label}`,
                            value: formatUgx(
                                currentPeriod.expected -
                                    currentPeriod.collected,
                            ),
                            badge: `due ${currentPeriod.due_date}`,
                            headline: `${formatUgx(currentPeriod.collected)} collected`,
                            detail: `of ${formatUgx(currentPeriod.expected)} expected club-wide`,
                            icon: IconCalendarEvent,
                        } satisfies StatCard,
                    ]
                  : []),
          ]
        : [];

    const queue = [
        {
            label: 'Payments to verify',
            count: officer.payments_awaiting_verification,
            href: paymentIndex(),
        },
        {
            label: 'Expenses to approve',
            count: officer.expenses_awaiting_approval,
            href: expenseIndex(),
        },
        {
            label: 'Expenses to verify',
            count: officer.expenses_awaiting_verification,
            href: expenseIndex(),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="@container/main flex flex-col gap-6 py-4 md:gap-8 md:py-6">
                {personalCards.length > 0 && (
                    <div className="flex flex-col gap-4">
                        <div className="px-4 lg:px-6">
                            <Heading
                                variant="small"
                                title="Your contributions"
                                description="What you owe and what has been verified"
                            />
                        </div>
                        <ClubStatCards cards={personalCards} />
                    </div>
                )}

                <div className="flex flex-col gap-4">
                    <div className="px-4 lg:px-6">
                        <Heading
                            variant="small"
                            title="Club position"
                            description={
                                club.reconciled_to
                                    ? `Last confirmed reconciliation: ${club.reconciled_to}`
                                    : 'No reconciliation confirmed yet — treat these figures as unconfirmed'
                            }
                        />
                    </div>
                    <ClubStatCards cards={clubCards} />
                </div>

                <div className="grid gap-4 px-4 lg:grid-cols-2 lg:px-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Waiting on an officer</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {queue.map((item) => (
                                <div
                                    key={item.label}
                                    className="flex items-center justify-between text-sm"
                                >
                                    <span className="flex items-center gap-2">
                                        {item.count > 0 ? (
                                            <IconAlertTriangle className="size-4 text-muted-foreground" />
                                        ) : (
                                            <IconCircleCheck className="size-4 text-muted-foreground" />
                                        )}
                                        {item.label}
                                    </span>
                                    <span className="flex items-center gap-3">
                                        <span className="font-semibold tabular-nums">
                                            {item.count}
                                        </span>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link href={item.href}>Open</Link>
                                        </Button>
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Governance</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <IconGavel className="size-4 text-muted-foreground" />
                                    Open votes
                                </span>
                                <span className="flex items-center gap-3">
                                    <span className="font-semibold tabular-nums">
                                        {governance.open_votes}
                                    </span>
                                    <Button size="sm" variant="outline" asChild>
                                        <Link href={proposalIndex()}>Open</Link>
                                    </Button>
                                </span>
                            </div>

                            <div className="border-t pt-3">
                                <p className="text-muted-foreground">
                                    Next meeting
                                </p>
                                {governance.next_meeting ? (
                                    <>
                                        <p className="font-medium">
                                            {governance.next_meeting.title}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {governance.next_meeting.reference}{' '}
                                            ·{' '}
                                            {governance.next_meeting.scheduled_for
                                                .slice(0, 16)
                                                .replace('T', ' ')}
                                        </p>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground">
                                        None scheduled.
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
