<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\OpenContributionPeriod;
use App\Enums\ContributionPeriodStatus;
use App\Enums\ObligationStatus;
use App\Http\Requests\OpenContributionPeriodRequest;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ContributionPeriodController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', ContributionPeriod::class);

        $status = $request->string('status')->value();
        $year = $request->string('year')->value();

        return Inertia::render('contribution-period/index', [
            'periods' => ContributionPeriod::query()
                ->withCount('obligations')
                ->withSum([
                    'obligations as expected_total' => fn (Builder $query): Builder => $query->whereNotIn('status', [
                        ObligationStatus::Waived->value,
                        ObligationStatus::Cancelled->value,
                    ]),
                ], 'amount')
                ->withSum('obligations as collected_total', 'amount_paid')
                ->when($status !== '', fn (Builder $query): Builder => $query
                    ->where('status', $status))
                ->when($year !== '', fn (Builder $query): Builder => $query
                    ->where('year', $year))
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->paginate(12)
                ->withQueryString(),
            'filters' => [
                'status' => $status === '' ? null : $status,
                'year' => $year === '' ? null : $year,
            ],
            'statusOptions' => array_map(
                static fn (ContributionPeriodStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                ContributionPeriodStatus::cases(),
            ),
            'yearOptions' => ContributionPeriod::query()
                ->select('year')
                ->distinct()
                ->orderByDesc('year')
                ->get()
                ->map(fn (ContributionPeriod $period): array => [
                    'value' => (string) $period->year,
                    'label' => (string) $period->year,
                ])
                ->all(),
            'canOpenPeriod' => $user->can('create', ContributionPeriod::class),
        ]);
    }

    public function show(ContributionPeriod $contributionPeriod, #[CurrentUser] User $user): Response
    {
        Gate::authorize('view', $contributionPeriod);

        return Inertia::render('contribution-period/show', [
            'period' => $contributionPeriod,
            'label' => $contributionPeriod->label(),
            'obligations' => $contributionPeriod->obligations()
                ->with('member')
                ->get()
                ->map(fn (MemberObligation $obligation): array => [
                    'id' => $obligation->id,
                    'member_name' => $obligation->member->full_name ?? __('Unknown member'),
                    'member_number' => $obligation->member->member_number ?? '',
                    'amount' => $obligation->amount,
                    'amount_paid' => $obligation->amount_paid,
                    'outstanding' => $obligation->outstanding(),
                    'status' => $obligation->status,
                    'adjustment_reason' => $obligation->adjustment_reason,
                    'can_adjust' => $user->can('adjust', $obligation),
                ]),
        ]);
    }

    public function store(
        OpenContributionPeriodRequest $request,
        #[CurrentUser] User $user,
        OpenContributionPeriod $action,
    ): RedirectResponse {
        $action->handle(
            $request->integer('year'),
            $request->integer('month'),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Contribution period opened.'),
        ]);

        return to_route('contribution-period.index');
    }
}
