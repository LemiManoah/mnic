<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ContributionPeriod;
use App\Models\ExternalAccount;
use App\Models\Reconciliation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Reconciliation::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contribution_period_id' => [
                'required',
                'string',
                Rule::exists(ContributionPeriod::class, 'id'),
                Rule::unique(Reconciliation::class, 'contribution_period_id')
                    ->where('external_account_id', $this->string('external_account_id')->value() ?: null),
            ],
            'external_account_id' => ['nullable', 'string', Rule::exists(ExternalAccount::class, 'id')],
            'opening_balance' => ['required', 'integer', 'min:0'],
            'statement_closing_balance' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
