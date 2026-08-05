import { Form, Head } from '@inertiajs/react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin/layout';
import AppLayout from '@/layouts/app-layout';
import { create as createMember, index as memberIndex } from '@/routes/member';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Members',
        href: memberIndex(),
    },
    {
        title: 'Add member',
        href: createMember(),
    },
];

export default function MemberCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add member" />

            <AdminLayout>
                <div className="max-w-xl space-y-6">
                    <Heading
                        variant="small"
                        title="Add member"
                        description="Onboard a new prospective member"
                    />

                    <Form
                        {...MemberController.store.form()}
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
                                        required
                                        placeholder="MN-0001"
                                    />
                                    <InputError
                                        message={errors.member_number}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="full_name">Full name</Label>
                                    <Input
                                        id="full_name"
                                        name="full_name"
                                        required
                                        autoComplete="name"
                                    />
                                    <InputError message={errors.full_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        required
                                        autoComplete="tel"
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="emergency_contact">
                                        Emergency contact
                                    </Label>
                                    <Input
                                        id="emergency_contact"
                                        name="emergency_contact"
                                    />
                                    <InputError
                                        message={errors.emergency_contact}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="joined_at">
                                        Joined date
                                    </Label>
                                    <Input
                                        id="joined_at"
                                        type="date"
                                        name="joined_at"
                                        required
                                    />
                                    <InputError message={errors.joined_at} />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Create member
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
