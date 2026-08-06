<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordPayment;
use App\Enums\PaymentMethod;
use App\Http\Requests\CreatePaymentRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PaymentController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['member', 'recordedByMember', 'reviewedByMember', 'evidence'])
            ->latest()
            ->paginate(20)
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
                'evidence' => $payment->evidence->map(fn (PaymentEvidence $evidence): array => [
                    'id' => $evidence->id,
                    'original_name' => $evidence->original_name,
                ]),
            ]);

        return Inertia::render('payment/index', [
            'payments' => $payments,
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
