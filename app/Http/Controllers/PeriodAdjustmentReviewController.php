<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApprovePeriodAdjustment;
use App\Actions\RejectPeriodAdjustment;
use App\Http\Requests\ReviewPeriodAdjustmentRequest;
use App\Models\Member;
use App\Models\PeriodAdjustment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class PeriodAdjustmentReviewController
{
    public function update(
        ReviewPeriodAdjustmentRequest $request,
        PeriodAdjustment $periodAdjustment,
        #[CurrentUser] User $user,
        ApprovePeriodAdjustment $action,
    ): RedirectResponse {
        $approver = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle($periodAdjustment, $approver, $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Adjustment approved.'),
        ]);

        return to_route('period-adjustment.index');
    }

    public function destroy(
        ReviewPeriodAdjustmentRequest $request,
        PeriodAdjustment $periodAdjustment,
        #[CurrentUser] User $user,
        RejectPeriodAdjustment $action,
    ): RedirectResponse {
        $approver = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle(
            $periodAdjustment,
            $approver,
            $request->string('reason')->value(),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Adjustment rejected.'),
        ]);

        return to_route('period-adjustment.index');
    }
}
