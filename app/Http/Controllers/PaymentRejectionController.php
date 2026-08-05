<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RejectPayment;
use App\Http\Requests\RejectPaymentRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use RuntimeException;

final readonly class PaymentRejectionController
{
    public function update(
        RejectPaymentRequest $request,
        Payment $payment,
        #[CurrentUser] User $user,
        RejectPayment $action,
    ): RedirectResponse {
        $verifier = Member::query()->firstWhere('user_id', $user->id);

        if ($verifier === null) {
            throw new RuntimeException('The reviewing user is not linked to a member record.');
        }

        $action->handle($payment, $verifier, $request->string('reason')->value(), $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment rejected.'),
        ]);

        return to_route('payment.index');
    }
}
