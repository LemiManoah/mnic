<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ChangeMemberStatus;
use App\Actions\CreateMember;
use App\Actions\UpdateMember;
use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Http\Requests\CreateMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class MemberController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Member::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->value();

        return Inertia::render('member/index', [
            'members' => Member::query()
                ->when($search !== '', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $inner): Builder => $inner
                        ->where('full_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('member_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('phone', 'like', sprintf('%%%s%%', $search))))
                ->when($status !== '', fn (Builder $query): Builder => $query
                    ->where('status', $status))
                ->orderBy('member_number')
                ->orderBy('id')
                ->paginate(20)
                // Keep the filters on the pagination links, otherwise page two
                // silently drops them.
                ->withQueryString(),
            'filters' => [
                'search' => $search === '' ? null : $search,
                'status' => $status === '' ? null : $status,
            ],
            'statusOptions' => array_map(
                static fn (MemberStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                MemberStatus::cases(),
            ),
        ]);
    }

    public function show(Member $member, #[CurrentUser] User $user): Response
    {
        Gate::authorize('view', $member);

        $member->loadMissing(['user', 'referredBy', 'currentPositionHolding']);

        $canViewActivity = $user->can('viewAny', AuditLog::class);
        $canUpdate = $user->can('update', $member);

        // Built up front rather than as a ternary in the render array: a
        // standalone `: []` arm is attributed to the statement's first line, so
        // coverage can never mark it hit even when a test takes that branch.
        $auditLogs = [];

        if ($canViewActivity) {
            $auditLogs = AuditLog::query()
                ->with('actorMember')
                ->where('auditable_type', Member::class)
                ->where('auditable_id', $member->id)
                ->latest()
                ->limit(50)
                ->get();
        }

        return Inertia::render('member/show', [
            // The linked user and referrer are hidden so a profile view does not
            // leak another member's account record; the fields the page needs
            // are passed explicitly below.
            'member' => $member->makeHidden(['user', 'referredBy']),
            'referredByName' => $member->referredBy?->full_name,
            'position' => $member->currentPosition()?->label(),
            'currentRole' => $member->user?->getRoleNames()->first(),
            'email' => $canUpdate ? $member->user?->email : null,
            'canUpdate' => $canUpdate,
            'canViewActivity' => $canViewActivity,
            'statusHistories' => $member->statusHistories()
                ->latest('effective_date')->latest()
                ->get(),
            'obligations' => $member->obligations()
                ->with('contributionPeriod')
                ->get()
                ->sortByDesc(fn (MemberObligation $obligation): string => sprintf(
                    '%04d-%02d',
                    $obligation->contributionPeriod->year ?? 0,
                    $obligation->contributionPeriod->month ?? 0,
                ))
                ->values()
                ->map(fn (MemberObligation $obligation): array => [
                    'id' => $obligation->id,
                    'period' => $obligation->contributionPeriod?->label() ?? '',
                    'amount' => $obligation->amount,
                    'amount_paid' => $obligation->amount_paid,
                    'outstanding' => $obligation->outstanding(),
                    'status' => $obligation->status,
                ]),
            'payments' => $member->payments()
                ->latest()
                ->get()
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'withdrawal_fee_amount' => $payment->withdrawal_fee_amount,
                    'contribution_amount' => $payment->contributionAmount(),
                    'unapplied_amount' => $payment->unapplied_amount,
                    'paid_on' => $payment->paid_on->toDateString(),
                    'method' => $payment->method,
                    'reference' => $payment->reference,
                    'status' => $payment->status,
                ]),
            'auditLogs' => $auditLogs,
        ]);
    }

    public function store(CreateMemberRequest $request, #[CurrentUser] User $user, CreateMember $action): RedirectResponse
    {
        $action->handle(
            $request->validated(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Member created.'),
        ]);

        return to_route('member.index');
    }

    public function edit(Member $member, #[CurrentUser] User $user): Response
    {
        Gate::authorize('update', $member);

        $member->loadMissing('user');

        return Inertia::render('member/edit', [
            'member' => $member,
            'canAssignRole' => $user->can('assignRole', $member),
            'currentRole' => $member->user?->getRoleNames()->first(),
            'roleOptions' => array_map(
                static fn (ClubRole $role): array => ['value' => $role->value, 'label' => $role->label()],
                ClubRole::cases(),
            ),
            'statusOptions' => array_map(
                static fn (MemberStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ChangeMemberStatus::allowedTransitionsFrom($member->status),
            ),
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member, UpdateMember $action): RedirectResponse
    {
        $action->handle($member, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Member updated.'),
        ]);

        return to_route('member.index');
    }
}
