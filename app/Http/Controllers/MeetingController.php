<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ScheduleMeeting;
use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Enums\MemberStatus;
use App\Http\Requests\ScheduleMeetingRequest;
use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Member;
use App\Models\Minute;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class MeetingController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Meeting::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->value();

        return Inertia::render('meeting/index', [
            'meetings' => Meeting::query()
                ->withCount([
                    'attendances as present_count' => fn (Builder $query): Builder => $query
                        ->where('status', AttendanceStatus::Present->value),
                ])
                ->when($search !== '', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $inner): Builder => $inner
                        ->where('title', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('reference', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('location', 'like', sprintf('%%%s%%', $search))))
                ->when($status !== '', fn (Builder $query): Builder => $query
                    ->where('status', $status))
                ->orderByDesc('scheduled_for')
                ->paginate(15)
                ->withQueryString(),
            'filters' => [
                'search' => $search === '' ? null : $search,
                'status' => $status === '' ? null : $status,
            ],
            'statusOptions' => array_map(
                static fn (MeetingStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                MeetingStatus::cases(),
            ),
            'canSchedule' => $user->can('create', Meeting::class),
        ]);
    }

    public function show(Meeting $meeting, #[CurrentUser] User $user): Response
    {
        Gate::authorize('view', $meeting);

        $meeting->loadMissing(['attendances.member', 'proposals', 'actionItems.ownerMember']);

        $latest = $meeting->latestMinute();

        return Inertia::render('meeting/show', [
            'meeting' => $meeting,
            'canManageMinutes' => $user->can('manageMinutes', $meeting),
            'activeMembers' => Member::query()
                ->where('status', MemberStatus::Active->value)
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'member_number']),
            'attendance' => $meeting->attendances->map(fn (MeetingAttendance $record): array => [
                'member_id' => $record->member_id,
                'member_name' => $record->member->full_name ?? __('Unknown member'),
                'status' => $record->status,
            ]),
            'minutes' => $latest instanceof Minute ? [
                'id' => $latest->id,
                'version' => $latest->version,
                'body' => $latest->body,
                'confirmed_at' => $latest->confirmed_at?->toDateTimeString(),
            ] : null,
            'minuteVersions' => $meeting->minutes()
                ->orderByDesc('version')
                ->get(['id', 'version', 'confirmed_at']),
            'proposals' => $meeting->proposals->map(fn (Proposal $proposal): array => [
                'id' => $proposal->id,
                'title' => $proposal->title,
                'status' => $proposal->status,
            ]),
            'actionItems' => $meeting->actionItems->map(fn (ActionItem $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'owner' => $item->ownerMember?->full_name,
                'due_on' => $item->due_on?->toDateString(),
                'status' => $item->status,
            ]),
        ]);
    }

    public function store(
        ScheduleMeetingRequest $request,
        #[CurrentUser] User $user,
        ScheduleMeeting $action,
    ): RedirectResponse {
        $action->handle(
            $request->validated(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Meeting scheduled.'),
        ]);

        return to_route('meeting.index');
    }
}
