import { Form, Head, Link } from '@inertiajs/react';
import MeetingAttendanceController from '@/actions/App/Http/Controllers/MeetingAttendanceController';
import MeetingMinutesController from '@/actions/App/Http/Controllers/MeetingMinutesController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { index as meetingIndex, show as showMeeting } from '@/routes/meeting';
import { show as showProposal } from '@/routes/proposal';
import type {
    AttendanceRow,
    BreadcrumbItem,
    Meeting,
    MeetingActionSummary,
    MeetingProposalSummary,
    MemberOption,
    MinuteSummary,
    MinuteVersion,
} from '@/types';

const ATTENDANCE_OPTIONS = ['present', 'apologies', 'absent'] as const;

export default function MeetingShow({
    meeting,
    canManageMinutes,
    activeMembers,
    attendance,
    minutes,
    minuteVersions,
    proposals,
    actionItems,
}: {
    meeting: Meeting;
    canManageMinutes: boolean;
    activeMembers: MemberOption[];
    attendance: AttendanceRow[];
    minutes: MinuteSummary | null;
    minuteVersions: MinuteVersion[];
    proposals: MeetingProposalSummary[];
    actionItems: MeetingActionSummary[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Meetings', href: meetingIndex() },
        { title: meeting.reference, href: showMeeting(meeting.id) },
    ];

    const attendanceFor = (memberId: string) =>
        attendance.find((row) => row.member_id === memberId)?.status ??
        'absent';

    const presentCount = attendance.filter(
        (row) => row.status === 'present',
    ).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={meeting.title} />

            <AdminLayout>
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        variant="small"
                        title={meeting.title}
                        description={`${meeting.reference} · ${meeting.scheduled_for.slice(0, 16).replace('T', ' ')}${meeting.location ? ` · ${meeting.location}` : ''}`}
                    />
                    <Badge variant="secondary">{meeting.status}</Badge>
                </div>

                <Tabs defaultValue="agenda">
                    <TabsList>
                        <TabsTrigger value="agenda">Agenda</TabsTrigger>
                        <TabsTrigger value="attendance">
                            Attendance ({presentCount})
                        </TabsTrigger>
                        <TabsTrigger value="minutes">Minutes</TabsTrigger>
                        <TabsTrigger value="decisions">Decisions</TabsTrigger>
                    </TabsList>

                    <TabsContent value="agenda">
                        <Card>
                            <CardHeader>
                                <CardTitle>Agenda</CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm whitespace-pre-wrap">
                                {meeting.agenda ?? (
                                    <span className="text-muted-foreground">
                                        No agenda published.
                                    </span>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="attendance">
                        <Card>
                            <CardHeader>
                                <CardTitle>Attendance</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {canManageMinutes ? (
                                    <Form
                                        {...MeetingAttendanceController.update.form(
                                            meeting.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                        className="space-y-4"
                                    >
                                        {({ processing }) => (
                                            <>
                                                <div className="overflow-x-auto">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>
                                                                    Member
                                                                </TableHead>
                                                                <TableHead>
                                                                    Attendance
                                                                </TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {activeMembers.map(
                                                                (member) => (
                                                                    <TableRow
                                                                        key={
                                                                            member.id
                                                                        }
                                                                    >
                                                                        <TableCell>
                                                                            {
                                                                                member.full_name
                                                                            }
                                                                        </TableCell>
                                                                        <TableCell>
                                                                            <select
                                                                                name={`attendance[${member.id}]`}
                                                                                defaultValue={attendanceFor(
                                                                                    member.id,
                                                                                )}
                                                                                className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                                                            >
                                                                                {ATTENDANCE_OPTIONS.map(
                                                                                    (
                                                                                        option,
                                                                                    ) => (
                                                                                        <option
                                                                                            key={
                                                                                                option
                                                                                            }
                                                                                            value={
                                                                                                option
                                                                                            }
                                                                                        >
                                                                                            {
                                                                                                option
                                                                                            }
                                                                                        </option>
                                                                                    ),
                                                                                )}
                                                                            </select>
                                                                        </TableCell>
                                                                    </TableRow>
                                                                ),
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>

                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    Save attendance
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>
                                                        Member
                                                    </TableHead>
                                                    <TableHead>
                                                        Attendance
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {attendance.map((row) => (
                                                    <TableRow
                                                        key={row.member_id}
                                                    >
                                                        <TableCell>
                                                            {row.member_name}
                                                        </TableCell>
                                                        <TableCell>
                                                            {row.status}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}

                                                {attendance.length === 0 && (
                                                    <TableRow>
                                                        <TableCell
                                                            colSpan={2}
                                                            className="py-6 text-center text-muted-foreground"
                                                        >
                                                            Attendance has not
                                                            been recorded.
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="minutes" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    Minutes
                                    {minutes
                                        ? ` — version ${minutes.version}`
                                        : ''}
                                    {minutes?.confirmed_at && (
                                        <Badge
                                            variant="default"
                                            className="ml-2"
                                        >
                                            confirmed
                                        </Badge>
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="text-sm whitespace-pre-wrap">
                                    {minutes?.body ?? (
                                        <span className="text-muted-foreground">
                                            No minutes published yet.
                                        </span>
                                    )}
                                </div>

                                {canManageMinutes &&
                                    minutes?.confirmed_at == null && (
                                        <>
                                            <Form
                                                {...MeetingMinutesController.store.form(
                                                    meeting.id,
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                                className="space-y-3"
                                            >
                                                {({ processing, errors }) => (
                                                    <>
                                                        <Textarea
                                                            name="body"
                                                            rows={8}
                                                            required
                                                            defaultValue={
                                                                minutes?.body ??
                                                                ''
                                                            }
                                                            placeholder="Discussion, decisions and follow-ups…"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.body
                                                            }
                                                        />
                                                        <Button
                                                            type="submit"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Publish new version
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>

                                            {minutes && (
                                                <Form
                                                    {...MeetingMinutesController.update.form(
                                                        meeting.id,
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            variant="outline"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Confirm these
                                                            minutes
                                                        </Button>
                                                    )}
                                                </Form>
                                            )}
                                        </>
                                    )}
                            </CardContent>
                        </Card>

                        {minuteVersions.length > 1 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Version history</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-1 text-sm text-muted-foreground">
                                        {minuteVersions.map((version) => (
                                            <li key={version.id}>
                                                Version {version.version}
                                                {version.confirmed_at
                                                    ? ' — confirmed'
                                                    : ''}
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    <TabsContent value="decisions" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Proposals</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-2 text-sm">
                                    {proposals.map((proposal) => (
                                        <li key={proposal.id}>
                                            <Link
                                                href={showProposal(proposal.id)}
                                                className="underline underline-offset-4"
                                            >
                                                {proposal.title}
                                            </Link>{' '}
                                            <Badge variant="secondary">
                                                {proposal.status}
                                            </Badge>
                                        </li>
                                    ))}

                                    {proposals.length === 0 && (
                                        <li className="text-muted-foreground">
                                            No proposals linked to this meeting.
                                        </li>
                                    )}
                                </ul>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Actions</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-2 text-sm">
                                    {actionItems.map((item) => (
                                        <li key={item.id}>
                                            {item.title}
                                            {item.owner
                                                ? ` — ${item.owner}`
                                                : ''}
                                            {item.due_on
                                                ? ` (due ${item.due_on})`
                                                : ''}{' '}
                                            <Badge variant="secondary">
                                                {item.status}
                                            </Badge>
                                        </li>
                                    ))}

                                    {actionItems.length === 0 && (
                                        <li className="text-muted-foreground">
                                            No actions assigned.
                                        </li>
                                    )}
                                </ul>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </AdminLayout>
        </AppLayout>
    );
}
