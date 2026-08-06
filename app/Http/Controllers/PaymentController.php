<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\CreatePaymentRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PaymentController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Payment::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->value();

        $payments = Payment::query()
            ->with(['member', 'recordedByMember', 'reviewedByMember', 'evidence'])
            ->when($search !== '', fn (Builder $query): Builder => $query
                ->where(fn (Builder $inner): Builder => $inner
                    ->where('reference', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('member', fn (Builder $member): Builder => $member
                        ->where('full_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('member_number', 'like', sprintf('%%%s%%', $search)))))
            ->when($status !== '', fn (Builder $query): Builder => $query
                ->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $payment): array => [
                'id' => $payment->id,
                'member_name' => $payment->member->full_name,
                'amount' => $payment->amount,
                'unapplied_amount' => $payment->unapplied_amount,
                'paid_on' => $payment->paid_on->toDateString(),
                'method' => $payment->method,
                'reference' => $payment->reference,
                'status' => $payment->status,
                'recorded_by' => $payment->recordedByMember?->full_name,
                'reviewed_by' => $payment->reviewedByMember?->full_name,
                'rejection_reason' => $payment->rejection_reason,
                'can_review' => $user->can('review', $payment),
                'can_reverse' => $user->can('reverse', $payment),
                'reversal_reason' => $payment->reversal_reason,
                'evidence' => $payment->evidence->map(fn (PaymentEvidence $evidence): array => [
                    'id' => $evidence->id,
                    'original_name' => $evidence->original_name,
                ]),
            ]);

        return Inertia::render('payment/index', [
            'payments' => $payments,
            'filters' => [
                'search' => $search === '' ? null : $search,
                'status' => $status === '' ? null : $status,
            ],
            'statusOptions' => array_map(
                static fn (PaymentStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                PaymentStatus::cases(),
            ),
            'members' => Member::query()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'member_number']),
            'methodOptions' => array_map(
                static fn (PaymentMethod $method): array => [
                    'value' => $method->value,
                    'label' => $method->label(),
                ],
                PaymentMethod::cases(),
            ),
        ]);
    }

    public function store(
        CreatePaymentRequest $request,
        #[CurrentUser] User $user,
        RecordPayment $action,
    ): RedirectResponse {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->safe()->except('evidence');

        $action->handle(
            $attributes,
            Member::query()->firstWhere('user_id', $user->id),
            $request->file('evidence'),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment submitted for verification.'),
        ]);

        return to_route('payment.index');
    }
}
