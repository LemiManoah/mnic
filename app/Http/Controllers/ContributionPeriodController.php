<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\OpenContributionPeriod;
use App\Http\Requests\OpenContributionPeriodRequest;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ContributionPeriodController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', ContributionPeriod::class);

        return Inertia::render('contribution-period/index', [
            'periods' => ContributionPeriod::query()
                ->withCount('obligations')
                ->withSum('obligations as expected_total', 'amount')
                ->withSum('obligations as collected_total', 'amount_paid')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->paginate(12),
            'canOpenPeriod' => $user->can('create', ContributionPeriod::class),
        ]);
    }

    public function show(ContributionPeriod $contributionPeriod): Response
    {
        Gate::authorize('view', $contributionPeriod);

        return Inertia::render('contribution-period/show', [
            'period' => $contributionPeriod,
            'label' => $contributionPeriod->label(),
            'obligations' => $contributionPeriod->obligations()
                ->with('member')
                ->get()
                ->map(fn ($obligation): array => [
                    'id' => $obligation->id,
                    'member_name' => $obligation->member->full_name,
                    'member_number' => $obligation->member->member_number,
                    'amount' => $obligation->amount,
                    'amount_paid' => $obligation->amount_paid,
                    'outstanding' => $obligation->outstanding(),
                    'status' => $obligation->status,
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
