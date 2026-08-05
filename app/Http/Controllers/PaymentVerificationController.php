<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\VerifyPayment;
use App\Http\Requests\VerifyPaymentRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class PaymentVerificationController
{
    public function update(
        VerifyPaymentRequest $request,
        Payment $payment,
        #[CurrentUser] User $user,
        VerifyPayment $action,
    ): RedirectResponse {
        // PaymentPolicy::review already guarantees the reviewer has a member
        // record, so this cannot miss.
        $verifier = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle($payment, $verifier, $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment verified.'),
        ]);

        return to_route('payment.index');
    }
}
