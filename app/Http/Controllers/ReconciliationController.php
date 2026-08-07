<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReconciliationStatus;
use App\Http\Requests\CreateReconciliationRequest;
use App\Models\ContributionPeriod;
use App\Models\ExternalAccount;
use App\Models\Member;
use App\Models\Reconciliation;
use App\Models\ReconciliationItem;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ReconciliationController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Reconciliation::class);

        $status = $request->string('status')->value();

        return Inertia::render('reconciliation/index', [
            'reconciliations' => Reconciliation::query()
                ->with(['contributionPeriod', 'externalAccount', 'items', 'preparedByMember'])
                ->when($status !== '', fn (Builder $query): Builder => $query
                    ->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Reconciliation $reconciliation): array => [
                    'id' => $reconciliation->id,
                    'period' => $reconciliation->contributionPeriod?->label() ?? '',
                    'account' => $reconciliation->externalAccount?->name,
                    'opening_balance' => $reconciliation->opening_balance,
                    'statement_closing_balance' => $reconciliation->statement_closing_balance,
                    'expected_closing_balance' => $reconciliation->expected_closing_balance,
                    'difference' => $reconciliation->difference,
                    'status' => $reconciliation->status,
                    'prepared_by' => $reconciliation->preparedByMember?->full_name,
                    'items' => $reconciliation->items
                        ->sortByDesc('created_at')
                        ->values()
                        ->map(fn (ReconciliationItem $item): array => [
                            'id' => $item->id,
                            'description' => $item->description,
                            'amount' => $item->amount,
                            'is_resolved' => $item->is_resolved,
                        ]),
                    'can_submit' => $user->can('update', $reconciliation),
                    'can_confirm' => $user->can('confirm', $reconciliation),
                    'can_lock' => $user->can('lock', $reconciliation),
                ]),
            'filters' => [
                'status' => $status === '' ? null : $status,
            ],
            'statusOptions' => array_map(
                static fn (ReconciliationStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                ReconciliationStatus::cases(),
            ),
            'canCreate' => $user->can('create', Reconciliation::class),
            'periods' => ContributionPeriod::query()
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->map(fn (ContributionPeriod $period): array => [
                    'id' => $period->id,
                    'label' => $period->label(),
                ]),
            'externalAccounts' => ExternalAccount::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'masked_identifier']),
            'members' => Member::query()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'member_number']),
        ]);
    }

    public function store(
        CreateReconciliationRequest $request,
        #[CurrentUser] User $user,
    ): RedirectResponse {
        Reconciliation::query()->create([
            ...$request->validated(),
            'status' => ReconciliationStatus::Draft,
            'prepared_by_member_id' => Member::query()->firstWhere('user_id', $user->id)?->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Reconciliation started.'),
        ]);

        return to_route('reconciliation.index');
    }
}
