<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RequestPeriodAdjustment;
use App\Enums\AdjustmentStatus;
use App\Enums\ContributionPeriodStatus;
use App\Http\Requests\RequestPeriodAdjustmentRequest;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\PeriodAdjustment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PeriodAdjustmentController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', PeriodAdjustment::class);

        $status = $request->string('status')->value();

        return Inertia::render('period-adjustment/index', [
            'adjustments' => PeriodAdjustment::query()
                ->with(['contributionPeriod', 'requestedByMember', 'reviewedByMember'])
                ->when($status !== '', fn (Builder $query): Builder => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString()
                ->through(fn (PeriodAdjustment $adjustment): array => [
                    'id' => $adjustment->id,
                    'period' => $adjustment->contributionPeriod?->label() ?? '',
                    'amount' => $adjustment->amount,
                    'reason' => $adjustment->reason,
                    'status' => $adjustment->status,
                    'requested_by' => $adjustment->requestedByMember?->full_name,
                    'reviewed_by' => $adjustment->reviewedByMember?->full_name,
                    'rejection_reason' => $adjustment->rejection_reason,
                    'can_review' => $user->can('review', $adjustment),
                ]),
            'filters' => ['status' => $status === '' ? null : $status],
            'statusOptions' => array_map(
                static fn (AdjustmentStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                AdjustmentStatus::cases(),
            ),
            'canCreate' => $user->can('create', PeriodAdjustment::class),
            // Only closed months can be adjusted, so only closed months are
            // offered.
            'closedPeriods' => ContributionPeriod::query()
                ->where('status', ContributionPeriodStatus::Closed->value)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->map(fn (ContributionPeriod $period): array => [
                    'id' => $period->id,
                    'label' => $period->label(),
                ]),
        ]);
    }

    public function store(
        RequestPeriodAdjustmentRequest $request,
        #[CurrentUser] User $user,
        RequestPeriodAdjustment $action,
    ): RedirectResponse {
        $period = ContributionPeriod::query()
            ->findOrFail($request->string('contribution_period_id')->value());

        // PeriodAdjustmentPolicy::create already guarantees a member record.
        $requester = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle(
            $period,
            $request->integer('amount'),
            $request->string('reason')->value(),
            $requester,
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Adjustment raised. It needs a second officer to approve it.'),
        ]);

        return to_route('period-adjustment.index');
    }
}
