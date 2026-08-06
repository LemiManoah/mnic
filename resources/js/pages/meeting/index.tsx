import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import MeetingController from '@/actions/App/Http/Controllers/MeetingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PaginationLinks from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { index as meetingIndex, show as showMeeting } from '@/routes/meeting';
import type {
    BreadcrumbItem,
    Meeting,
    MeetingStatus,
    Paginated,
} from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meetings',
        href: meetingIndex(),
    },
];

const STATUS_VARIANT: Record<
    MeetingStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    scheduled: 'outline',
    completed: 'secondary',
    confirmed: 'default',
    cancelled: 'destructive',
};

function ScheduleMeetingDialog() {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Schedule meeting</Button>
            </DialogTrigger>

            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Schedule meeting</DialogTitle>
                    <DialogDescription>
                        Publish the agenda ahead of time so members can prepare.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...MeetingController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="reference">Reference</Label>
                                <Input
                                    id="reference"
                                    name="reference"
                                    required
                                    placeholder="MTG-2026-001"
                                />
                                <InputError message={errors.reference} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input id="title" name="title" required />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="scheduled_for">
                                    Scheduled for
                                </Label>
                                <Input
                                    id="scheduled_for"
                                    name="scheduled_for"
                                    type="datetime-local"
                                    required
                                />
                                <InputError message={errors.scheduled_for} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="location">Location</Label>
                                <Input id="location" name="location" />
                                <InputError message={errors.location} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="agenda">Agenda</Label>
                                <Textarea id="agenda" name="agenda" rows={4} />
                                <InputError message={errors.agenda} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Schedule
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function MeetingIndex({
    meetings,
    canSchedule,
}: {
    meetings: Paginated<Meeting>;
    canSchedule: boolean;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Meetings" />

            <AdminLayout>
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Meetings"
                        description="Agendas, attendance and confirmed minutes"
                    />

                    {canSchedule && <ScheduleMeetingDialog />}
                </div>

                <div className="overflow-x-auto rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Reference</TableHead>
                                <TableHead>Title</TableHead>
                                <TableHead>When</TableHead>
                                <TableHead>Present</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {meetings.data.map((meeting) => (
                                <TableRow key={meeting.id}>
                                    <TableCell className="font-medium">
                                        {meeting.reference}
                                    </TableCell>
                                    <TableCell>{meeting.title}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {meeting.scheduled_for
                                            .slice(0, 16)
                                            .replace('T', ' ')}
                                    </TableCell>
                                    <TableCell>
                                        {meeting.present_count ?? 0}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                STATUS_VARIANT[meeting.status]
                                            }
                                        >
                                            {meeting.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                        >
                                            <Link
                                                href={showMeeting(meeting.id)}
                                            >
                                                View
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}

                            {meetings.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-6 text-center text-muted-foreground"
                                    >
                                        No meetings scheduled yet.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <PaginationLinks links={meetings.links} />
            </AdminLayout>
        </AppLayout>
    );
}
