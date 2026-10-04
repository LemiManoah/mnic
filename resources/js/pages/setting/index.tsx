import { Form, Head } from '@inertiajs/react';
import SettingController from '@/actions/App/Http/Controllers/SettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { formatClubDate } from '@/lib/date';
import { formatUgx } from '@/lib/money';
import { index as settingIndex } from '@/routes/setting';
import type { BreadcrumbItem, SettingWithVersions } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Club settings', href: settingIndex() },
];

type SettingGuide = {
    title: string;
    description: string;
    inputLabel: string;
    help: string;
    min: number;
    max?: number;
};

const guides: Record<string, SettingGuide> = {
    contribution_amount: {
        title: 'Monthly contribution',
        description:
            'The amount each active member is expected to contribute when a new period opens.',
        inputLabel: 'Amount (UGX)',
        help: 'Applies to periods opened after this version takes effect. Existing obligations keep their amount.',
        min: 1,
    },
    due_day: {
        title: 'Contribution due day',
        description: 'The day of the month members should aim to pay by.',
        inputLabel: 'Day of the month',
        help: 'Use 1–28 so the date exists every month. This should be on or before the grace day.',
        min: 1,
        max: 28,
    },
    grace_day: {
        title: 'Contribution grace day',
        description:
            'The last day before an unpaid balance is treated as overdue.',
        inputLabel: 'Day of the month',
        help: 'Use 1–28. A grace day of 10 means overdue from the 11th. Later payments are still accepted.',
        min: 1,
        max: 28,
    },
    quorum_percent: {
        title: 'Voting quorum',
        description:
            'The minimum share of eligible members who must vote for a valid outcome.',
        inputLabel: 'Required participation (%)',
        help: 'The required headcount is rounded up. With 20 eligible members and 50%, at least 10 must vote. Abstentions count toward proposal quorum.',
        min: 1,
        max: 100,
    },
    approval_percent: {
        title: 'Proposal approval threshold',
        description:
            'The minimum percentage of For votes among For and Against votes, once quorum is met.',
        inputLabel: 'Required approval (%)',
        help: 'Abstentions are excluded. At 50%, a 5–5 tie passes. This setting applies to proposals, not officer-election winners.',
        min: 1,
        max: 100,
    },
};

function displayValue(setting: SettingWithVersions, value: string): string {
    const number = Number(value);

    if (!Number.isFinite(number)) return value;
    if (setting.key === 'contribution_amount') return formatUgx(number);
    if (setting.key.endsWith('_percent')) return `${number}%`;
    if (setting.key === 'due_day' || setting.key === 'grace_day')
        return `Day ${number}`;

    return value;
}

function SettingCard({
    setting,
    today,
}: {
    setting: SettingWithVersions;
    today: string;
}) {
    const guide = guides[setting.key];
    const scheduled = setting.versions.filter(
        (version) => version.effective_from.slice(0, 10) > today,
    );

    return (
        <Card className="min-w-0">
            <CardHeader className="space-y-3">
                <CardTitle className="text-base">
                    {guide?.title ?? setting.label}
                </CardTitle>
                {guide && (
                    <p className="text-sm leading-relaxed text-muted-foreground">
                        {guide.description}
                    </p>
                )}
                <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span className="text-2xl font-semibold tabular-nums">
                        {setting.current
                            ? displayValue(setting, setting.current.value)
                            : 'Not set'}
                    </span>
                    <span className="text-xs text-muted-foreground">
                        {setting.current
                            ? `In effect since ${formatClubDate(setting.current.effective_from)}`
                            : 'No effective version'}
                    </span>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {scheduled.length > 0 && (
                    <div className="space-y-2 rounded-md bg-muted/50 p-3 text-sm">
                        <Badge variant="secondary">Scheduled changes</Badge>
                        {scheduled.map((version) => (
                            <p key={version.id}>
                                <span className="font-medium">
                                    {displayValue(setting, version.value)}
                                </span>{' '}
                                from {formatClubDate(version.effective_from)}
                            </p>
                        ))}
                    </div>
                )}
                {guide && (
                    <p
                        id={`help-${setting.id}`}
                        className="text-sm leading-relaxed text-muted-foreground"
                    >
                        {guide.help}
                    </p>
                )}
                {setting.can_update ? (
                    <details className="rounded-md border px-4">
                        <summary className="cursor-pointer py-3 text-sm font-medium">
                            Change this setting
                        </summary>
                        <Form
                            {...SettingController.update.form(setting.id)}
                            options={{ preserveScroll: true }}
                            resetOnSuccess
                            className="grid min-w-0 gap-4 pb-4 sm:grid-cols-2"
                        >
                            {({ processing, errors, recentlySuccessful }) => (
                                <>
                                    <div className="grid min-w-0 gap-2">
                                        <Label htmlFor={`value-${setting.id}`}>
                                            {guide?.inputLabel ?? 'New value'}
                                        </Label>
                                        <Input
                                            id={`value-${setting.id}`}
                                            name="value"
                                            type={guide ? 'number' : 'text'}
                                            min={guide?.min}
                                            max={guide?.max}
                                            step={guide ? 1 : undefined}
                                            defaultValue={
                                                setting.current?.value ?? ''
                                            }
                                            aria-describedby={
                                                guide
                                                    ? `help-${setting.id}`
                                                    : undefined
                                            }
                                            aria-invalid={!!errors.value}
                                            required
                                        />
                                        <InputError message={errors.value} />
                                    </div>
                                    <div className="grid min-w-0 gap-2">
                                        <Label
                                            htmlFor={`effective-${setting.id}`}
                                        >
                                            Effective from
                                        </Label>
                                        <Input
                                            id={`effective-${setting.id}`}
                                            type="date"
                                            name="effective_from"
                                            defaultValue={today}
                                            className="min-w-0"
                                            aria-invalid={
                                                !!errors.effective_from
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.effective_from}
                                        />
                                    </div>
                                    <div className="col-span-full flex flex-wrap items-center gap-3">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-full sm:w-auto"
                                        >
                                            {processing
                                                ? 'Saving…'
                                                : 'Save change'}
                                        </Button>
                                        {recentlySuccessful && (
                                            <p
                                                role="status"
                                                className="text-sm text-muted-foreground"
                                            >
                                                Change saved.
                                            </p>
                                        )}
                                    </div>
                                </>
                            )}
                        </Form>
                    </details>
                ) : (
                    <p className="text-xs text-muted-foreground">
                        Only authorised officers can change this setting.
                    </p>
                )}
                <details className="border-t pt-3">
                    <summary className="cursor-pointer py-1 text-sm text-muted-foreground">
                        Version history ({setting.versions.length})
                    </summary>
                    <ul className="mt-3 divide-y text-sm">
                        {setting.versions.map((version) => (
                            <li
                                key={version.id}
                                className="flex flex-wrap items-center justify-between gap-2 py-3"
                            >
                                <div>
                                    <p className="font-medium">
                                        {displayValue(setting, version.value)}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        From{' '}
                                        {formatClubDate(version.effective_from)}
                                    </p>
                                </div>
                                <Badge
                                    variant={
                                        setting.current?.id === version.id
                                            ? 'default'
                                            : 'outline'
                                    }
                                >
                                    {setting.current?.id === version.id
                                        ? 'Current'
                                        : version.effective_from.slice(0, 10) >
                                            today
                                          ? 'Scheduled'
                                          : 'Previous'}
                                </Badge>
                            </li>
                        ))}
                    </ul>
                </details>
            </CardContent>
        </Card>
    );
}

export default function SettingIndex({
    settings,
    today,
}: {
    settings: SettingWithVersions[];
    today: string;
}) {
    const contributionKeys = ['contribution_amount', 'due_day', 'grace_day'];
    const votingKeys = ['quorum_percent', 'approval_percent'];
    const groups = [
        {
            title: 'Contributions',
            description:
                'Amounts and payment dates are copied into each period when it opens. Changing these settings does not change an already-open period.',
            items: contributionKeys.flatMap((key) =>
                settings.filter((setting) => setting.key === key),
            ),
        },
        {
            title: 'Voting rules',
            description:
                'Voting rules are copied when voting opens. A settings change does not alter a vote already in progress.',
            items: votingKeys.flatMap((key) =>
                settings.filter((setting) => setting.key === key),
            ),
        },
        {
            title: 'Other settings',
            description: 'Additional club configuration.',
            items: settings.filter(
                (setting) =>
                    ![...contributionKeys, ...votingKeys].includes(setting.key),
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Club settings" />
            <AdminLayout>
                <Heading
                    variant="small"
                    title="Club settings"
                    description="Understand the current rules, plan changes, and keep a clear history."
                />
                <div className="space-y-8">
                    {groups
                        .filter((group) => group.items.length > 0)
                        .map((group) => (
                            <section key={group.title} className="space-y-4">
                                <div>
                                    <h2 className="text-lg font-semibold">
                                        {group.title}
                                    </h2>
                                    <p className="mt-1 max-w-3xl text-sm leading-relaxed text-muted-foreground">
                                        {group.description}
                                    </p>
                                </div>
                                <div className="grid items-start gap-4 lg:grid-cols-2">
                                    {group.items.map((setting) => (
                                        <SettingCard
                                            key={setting.id}
                                            setting={setting}
                                            today={today}
                                        />
                                    ))}
                                </div>
                            </section>
                        ))}
                    {settings.length === 0 && (
                        <p className="rounded-lg border p-6 text-sm text-muted-foreground">
                            No club settings have been configured yet.
                        </p>
                    )}
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
