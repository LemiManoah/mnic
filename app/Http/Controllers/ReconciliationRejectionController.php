<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RejectReconciliation;
use App\Http\Requests\RejectReconciliationRequest;
use App\Models\Member;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class ReconciliationRejectionController
{
    public function update(
        RejectReconciliationRequest $request,
        Reconciliation $reconciliation,
        #[CurrentUser] User $user,
        RejectReconciliation $action,
    ): RedirectResponse {
        // ReconciliationPolicy::confirm already guarantees a member record.
        $reviewer = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle($reconciliation, $reviewer, $request->string('reason')->value(), $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Reconciliation returned for correction.'),
        ]);

        return to_route('reconciliation.index');
    }
}
