<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TransferPosition;
use App\Enums\ClubPosition;
use App\Enums\MemberStatus;
use App\Enums\Permission;
use App\Http\Requests\TransferPositionRequest;
use App\Models\Member;
use App\Models\PositionHolding;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PositionController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Member::class);

        $currentHoldings = PositionHolding::query()
            ->with('member')
            ->whereNull('held_to')
            ->get()
            ->keyBy(fn (PositionHolding $holding): string => $holding->position->value);

        return Inertia::render('position/index', [
            'positions' => array_map(
                fn (ClubPosition $position): array => [
                    'value' => $position->value,
                    'label' => $position->label(),
                    'holder' => $currentHoldings->get($position->value)?->member?->full_name,
                    'held_from' => $currentHoldings->get($position->value)?->held_from?->toDateString(),
                ],
                ClubPosition::cases(),
            ),
            'history' => PositionHolding::query()
                ->with(['member', 'electedViaPositionPoll'])
                ->latest('held_from')
                ->latest()
                ->get()
                ->map(fn (PositionHolding $holding): array => [
                    'id' => $holding->id,
                    'position' => $holding->position->label(),
                    'member_name' => $holding->member->full_name ?? __('Unknown member'),
                    'held_from' => $holding->held_from->toDateString(),
                    'held_to' => $holding->held_to?->toDateString(),
                    'source' => $holding->electedViaPositionPoll->title ?? $holding->transfer_reason,
                ]),
            'members' => Member::query()
                ->where('status', MemberStatus::Active->value)
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'member_number']),
            'positionOptions' => array_map(
                static fn (ClubPosition $position): array => [
                    'value' => $position->value,
                    'label' => $position->label(),
                ],
                ClubPosition::cases(),
            ),
            'canTransfer' => $user->can(Permission::PositionsManage->value),
        ]);
    }

    public function store(
        TransferPositionRequest $request,
        #[CurrentUser] User $user,
        TransferPosition $action,
    ): RedirectResponse {
        $actor = Member::query()->where('user_id', $user->id)->firstOrFail();
        $successor = Member::query()->findOrFail($request->string('member_id')->value());

        $action->handle(
            ClubPosition::from($request->string('position')->value()),
            $successor,
            $request->string('held_from')->value(),
            $actor,
            null,
            $request->string('reason')->value(),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Position transferred.'),
        ]);

        return to_route('position.index');
    }
}
