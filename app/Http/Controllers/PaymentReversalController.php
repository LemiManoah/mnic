<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApprovePaymentReversal;
use App\Actions\RejectPaymentReversal;
use App\Actions\RequestPaymentReversal;
use App\Http\Requests\RequestPaymentReversalRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final readonly class PaymentReversalController
{
    /**
     * Ask for a verified payment to be unwound.
     */
    public function store(
        RequestPaymentReversalRequest $request,
        Payment $payment,
        #[CurrentUser] User $user,
        RequestPaymentReversal $action,
    ): RedirectResponse {
        $action->handle(
            $payment,
            Member::query()->where('user_id', $user->id)->firstOrFail(),
            $request->string('reason')->value(),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Reversal requested. It needs a second officer to approve.'),
        ]);

        return to_route('payment.index');
    }

    /**
     * Approve a pending reversal and unwind the allocations.
     */
    public function update(
        Request $request,
        Payment $payment,
        #[CurrentUser] User $user,
        ApprovePaymentReversal $action,
    ): RedirectResponse {
        Gate::authorize('decideReversal', $payment);

        $action->handle(
            $payment,
            Member::query()->where('user_id', $user->id)->firstOrFail(),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment reversed.'),
        ]);

        return to_route('payment.index');
    }

    /**
     * Turn down a pending reversal and leave the payment verified.
     */
    public function destroy(
        Request $request,
        Payment $payment,
        #[CurrentUser] User $user,
        RejectPaymentReversal $action,
    ): RedirectResponse {
        Gate::authorize('decideReversal', $payment);

        $action->handle(
            $payment,
            Member::query()->where('user_id', $user->id)->firstOrFail(),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Reversal request declined. The payment stays verified.'),
        ]);

        return to_route('payment.index');
    }
}
