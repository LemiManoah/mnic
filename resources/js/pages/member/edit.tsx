import { Form, Head } from '@inertiajs/react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import MemberRoleController from '@/actions/App/Http/Controllers/MemberRoleController';
import MemberStatusController from '@/actions/App/Http/Controllers/MemberStatusController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { edit as editMember, index as memberIndex } from '@/routes/member';
import type { BreadcrumbItem, Member, Option } from '@/types';

export default function MemberEdit({
    member,
    canAssignRole,
    currentRole,
    roleOptions,
    statusOptions,
}: {
    member: Member;
    canAssignRole: boolean;
    currentRole?: string;
    roleOptions: Option[];
    statusOptions: Option[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Members',
            href: memberIndex(),
        },
        {
            title: member.full_name,
            href: editMember(member.id),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={member.full_name} />

            <AdminLayout>
                <div className="max-w-xl space-y-8">
                    <Heading
                        variant="small"
                        title={member.full_name}
                        description={`Member #${member.member_number}`}
                    />

                    <Card>
                        <CardHeader>
                            <CardTitle>Profile</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...MemberController.update.form(member.id)}
                                className="space-y-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="member_number">
                                                Member number
                                            </Label>
                                            <Input
                                                id="member_number"
                                                name="member_number"
                                                defaultValue={
                                                    member.member_number
                                                }
                                                required
                                            />
                                            <InputError
                                                message={errors.member_number}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="full_name">
                                                Full name
                                            </Label>
                                            <Input
                                                id="full_name"
                                                name="full_name"
                                                defaultValue={member.full_name}
                                                required
                                            />
                                            <InputError
                                                message={errors.full_name}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="phone">Phone</Label>
                                            <Input
                                                id="phone"
                                                name="phone"
                                                defaultValue={member.phone}
                                                required
                                            />
                                            <InputError
                                                message={errors.phone}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="emergency_contact">
                                                Emergency contact
                                            </Label>
                                            <Input
                                                id="emergency_contact"
                                                name="emergency_contact"
                                                defaultValue={
                                                    member.emergency_contact ??
                                                    ''
                                                }
                                            />
                                            <InputError
                                                message={
                                                    errors.emergency_contact
                                                }
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Save profile
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>

                    {statusOptions.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Change status</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...MemberStatusController.update.form(
                                        member.id,
                                    )}
                                    className="space-y-6"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="to_status">
                                                    New status
                                                </Label>
                                                <select
                                                    id="to_status"
                                                    name="to_status"
                                                    required
                                                    defaultValue=""
                                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                                >
                                                    <option value="" disabled>
                                                        Select a status
                                                    </option>
                                                    {statusOptions.map(
                                                        (option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                                <InputError
                                                    message={errors.to_status}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="reason">
                                                    Reason
                                                </Label>
                                                <Input
                                                    id="reason"
                                                    name="reason"
                                                    required
                                                />
                                                <InputError
                                                    message={errors.reason}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="effective_date">
                                                    Effective date
                                                </Label>
                                                <Input
                                                    id="effective_date"
                                                    type="date"
                                                    name="effective_date"
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors.effective_date
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="resolution_reference">
                                                    Resolution reference
                                                </Label>
                                                <Input
                                                    id="resolution_reference"
                                                    name="resolution_reference"
                                                />
                                                <InputError
                                                    message={
                                                        errors.resolution_reference
                                                    }
                                                />
                                            </div>

                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Update status
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}

                    {canAssignRole && member.user_id && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Assign role</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    {...MemberRoleController.update.form(
                                        member.id,
                                    )}
                                    className="space-y-6"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="role">
                                                    Role
                                                </Label>
                                                <select
                                                    id="role"
                                                    name="role"
                                                    required
                                                    defaultValue={
                                                        currentRole ?? ''
                                                    }
                                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                                >
                                                    <option value="" disabled>
                                                        Select a role
                                                    </option>
                                                    {roleOptions.map(
                                                        (option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                                <InputError
                                                    message={errors.role}
                                                />
                                            </div>

                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Update role
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
