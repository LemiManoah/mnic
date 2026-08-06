<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ReversePayment;
use App\Http\Requests\ReversePaymentRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class PaymentReversalController
{
    public function update(
        ReversePaymentRequest $request,
        Payment $payment,
        #[CurrentUser] User $user,
        ReversePayment $action,
    ): RedirectResponse {
        $reverser = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle($payment, $reverser, $request->string('reason')->value(), $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment reversed.'),
        ]);

        return to_route('payment.index');
    }
}
