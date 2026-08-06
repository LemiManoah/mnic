<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Expense;
use App\Models\ExternalAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordExpensePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('expense');
        assert($expense instanceof Expense);

        return (bool) $this->user()?->can('pay', $expense);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'external_account_id' => ['required', 'string', Rule::exists(ExternalAccount::class, 'id')],
            'payment_reference' => ['required', 'string', 'max:255'],
            'paid_on' => ['required', 'date'],
        ];
    }
}
