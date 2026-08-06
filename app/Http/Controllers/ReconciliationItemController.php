<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordReconciliationItem;
use App\Actions\ResolveReconciliationItem;
use App\Http\Requests\RecordReconciliationItemRequest;
use App\Models\Member;
use App\Models\Reconciliation;
use App\Models\ReconciliationItem;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final readonly class ReconciliationItemController
{
    public function store(
        RecordReconciliationItemRequest $request,
        Reconciliation $reconciliation,
        #[CurrentUser] User $user,
        RecordReconciliationItem $action,
    ): RedirectResponse {
        $assignedTo = $request->filled('assigned_to_member_id')
            ? Member::query()->find($request->string('assigned_to_member_id')->value())
            : null;

        $action->handle(
            $reconciliation,
            $request->string('description')->value(),
            $request->integer('amount'),
            $assignedTo,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Reconciliation item recorded.'),
        ]);

        return to_route('reconciliation.index');
    }

    public function update(
        Reconciliation $reconciliation,
        ReconciliationItem $item,
        #[CurrentUser] User $user,
        ResolveReconciliationItem $action,
    ): RedirectResponse {
        Gate::authorize('update', $reconciliation);

        $action->handle(
            $item,
            Member::query()->firstWhere('user_id', $user->id),
            request()->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Reconciliation item resolved.'),
        ]);

        return to_route('reconciliation.index');
    }
}
