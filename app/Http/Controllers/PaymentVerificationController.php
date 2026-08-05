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
use RuntimeException;

final readonly class PaymentVerificationController
{
    public function update(
        VerifyPaymentRequest $request,
        Payment $payment,
        #[CurrentUser] User $user,
        VerifyPayment $action,
    ): RedirectResponse {
        $verifier = Member::query()->firstWhere('user_id', $user->id);

        if ($verifier === null) {
            throw new RuntimeException('The verifying user is not linked to a member record.');
        }

        $action->handle($payment, $verifier, $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment verified.'),
        ]);

        return to_route('payment.index');
    }
}
