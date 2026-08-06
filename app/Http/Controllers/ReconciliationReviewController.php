<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CloseMonth;
use App\Actions\ConfirmReconciliation;
use App\Actions\SubmitReconciliation;
use App\Http\Requests\ReviewReconciliationRequest;
use App\Models\Member;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class ReconciliationReviewController
{
    /**
     * Treasurer submits the prepared reconciliation for review.
     */
    public function store(
        ReviewReconciliationRequest $request,
        Reconciliation $reconciliation,
        #[CurrentUser] User $user,
        SubmitReconciliation $action,
    ): RedirectResponse {
        $action->handle(
            $reconciliation,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Reconciliation submitted for review.'),
        ]);

        return to_route('reconciliation.index');
    }

    /**
     * A different officer confirms it.
     */
    public function update(
        ReviewReconciliationRequest $request,
        Reconciliation $reconciliation,
        #[CurrentUser] User $user,
        ConfirmReconciliation $action,
    ): RedirectResponse {
        // ReconciliationPolicy::confirm already guarantees a member record.
        $confirmer = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle($reconciliation, $confirmer, $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Reconciliation confirmed.'),
        ]);

        return to_route('reconciliation.index');
    }

    /**
     * Locking closes the month for good.
     */
    public function destroy(
        ReviewReconciliationRequest $request,
        Reconciliation $reconciliation,
        #[CurrentUser] User $user,
        CloseMonth $action,
    ): RedirectResponse {
        $action->handle(
            $reconciliation,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Month closed and locked.'),
        ]);

        return to_route('reconciliation.index');
    }
}
