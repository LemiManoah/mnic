<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateActionItem;
use App\Actions\UpdateActionItemStatus;
use App\Enums\ActionItemStatus;
use App\Enums\MemberStatus;
use App\Http\Requests\CreateActionItemRequest;
use App\Http\Requests\UpdateActionItemRequest;
use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ActionItemController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', ActionItem::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->value();

        return Inertia::render('action-item/index', [
            'actionItems' => ActionItem::query()
                ->with(['ownerMember', 'meeting'])
                ->when($search !== '', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $inner): Builder => $inner
                        ->where('title', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('description', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('ownerMember', fn (Builder $member): Builder => $member
                            ->where('full_name', 'like', sprintf('%%%s%%', $search)))))
                ->when($status !== '', fn (Builder $query): Builder => $query
                    ->where('status', $status))
                ->orderByRaw('due_on is null, due_on asc')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (ActionItem $item): array => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'owner' => $item->ownerMember?->full_name,
                    'meeting' => $item->meeting?->reference,
                    'due_on' => $item->due_on?->toDateString(),
                    'status' => $item->status,
                    'can_update' => $user->can('update', $item),
                ]),
            'filters' => [
                'search' => $search === '' ? null : $search,
                'status' => $status === '' ? null : $status,
            ],
            'canCreate' => $user->can('create', ActionItem::class),
            'members' => Member::query()
                ->where('status', MemberStatus::Active->value)
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'member_number']),
            'meetings' => Meeting::query()
                ->orderByDesc('scheduled_for')
                ->get(['id', 'reference', 'title']),
            'statusOptions' => array_map(
                static fn (ActionItemStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                ActionItemStatus::cases(),
            ),
        ]);
    }

    public function store(
        CreateActionItemRequest $request,
        #[CurrentUser] User $user,
        CreateActionItem $action,
    ): RedirectResponse {
        $action->handle(
            $request->validated(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Action assigned.'),
        ]);

        return to_route('action-item.index');
    }

    public function update(
        UpdateActionItemRequest $request,
        ActionItem $actionItem,
        #[CurrentUser] User $user,
        UpdateActionItemStatus $action,
    ): RedirectResponse {
        $action->handle(
            $actionItem,
            ActionItemStatus::from($request->string('status')->value()),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Action updated.'),
        ]);

        return to_route('action-item.index');
    }
}
