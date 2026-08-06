import { Head, Link } from '@inertiajs/react';
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

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-sm text-muted-foreground">
                    {label}
                </CardTitle>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
                {value}
            </CardContent>
        </Card>
    );
}

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
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="space-y-8 px-4 py-6">
                {personal && (
                    <section className="space-y-4">
                        <Heading
                            variant="small"
                            title="Your contributions"
                            description="What you owe and what has been verified"
                        />
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Stat
                                label="Outstanding"
                                value={formatUgx(personal.outstanding)}
                            />
                            <Stat
                                label="Verified to date"
                                value={formatUgx(personal.verified_total)}
                            />
                            <Stat
                                label="Unapplied advance"
                                value={formatUgx(personal.advance)}
                            />
                        </div>
                    </section>
                )}

                <section className="space-y-4">
                    <Heading
                        variant="small"
                        title="Club position"
                        description={
                            club.reconciled_to
                                ? `Last confirmed reconciliation: ${club.reconciled_to}`
                                : 'No reconciliation confirmed yet — these figures are unconfirmed.'
                        }
                    />
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Stat
                            label="Net position"
                            value={formatUgx(club.net_position)}
                        />
                        <Stat
                            label="Verified inflows"
                            value={formatUgx(club.verified_inflows)}
                        />
                        <Stat
                            label="Settled outflows"
                            value={formatUgx(club.settled_outflows)}
                        />
                    </div>
                </section>

                {currentPeriod && (
                    <section className="space-y-4">
                        <Heading
                            variant="small"
                            title={`Current period ${currentPeriod.label}`}
                            description={`Due ${currentPeriod.due_date}`}
                        />
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Stat
                                label="Expected"
                                value={formatUgx(currentPeriod.expected)}
                            />
                            <Stat
                                label="Collected"
                                value={formatUgx(currentPeriod.collected)}
                            />
                            <Stat
                                label="Outstanding"
                                value={formatUgx(
                                    currentPeriod.expected -
                                        currentPeriod.collected,
                                )}
                            />
                        </div>
                    </section>
                )}

                <section className="space-y-4">
                    <Heading
                        variant="small"
                        title="Needs attention"
                        description="Work waiting on an officer"
                    />
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Stat
                            label="Payments to verify"
                            value={String(
                                officer.payments_awaiting_verification,
                            )}
                        />
                        <Stat
                            label="Expenses to approve"
                            value={String(officer.expenses_awaiting_approval)}
                        />
                        <Stat
                            label="Expenses to verify"
                            value={String(
                                officer.expenses_awaiting_verification,
                            )}
                        />
                        <Stat
                            label="Members in arrears"
                            value={String(officer.members_in_arrears)}
                        />
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button size="sm" variant="outline" asChild>
                            <Link href={paymentIndex()}>Payments</Link>
                        </Button>
                        <Button size="sm" variant="outline" asChild>
                            <Link href={expenseIndex()}>Expenses</Link>
                        </Button>
                        <Button size="sm" variant="outline" asChild>
                            <Link href={proposalIndex()}>Proposals</Link>
                        </Button>
                    </div>
                </section>

                <section className="space-y-4">
                    <Heading variant="small" title="Governance" />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Stat
                            label="Open votes"
                            value={String(governance.open_votes)}
                        />
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm text-muted-foreground">
                                    Next meeting
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm">
                                {governance.next_meeting ? (
                                    <>
                                        <div className="font-medium">
                                            {governance.next_meeting.title}
                                        </div>
                                        <div className="text-muted-foreground">
                                            {governance.next_meeting.reference}{' '}
                                            ·{' '}
                                            {governance.next_meeting.scheduled_for
                                                .slice(0, 16)
                                                .replace('T', ' ')}
                                        </div>
                                    </>
                                ) : (
                                    <span className="text-muted-foreground">
                                        None scheduled.
                                    </span>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
