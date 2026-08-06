<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class RequestExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Expense::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:255', Rule::unique(Expense::class, 'reference')],
            'purpose' => ['required', 'string', 'max:255'],
            'category' => ['required', new Enum(ExpenseCategory::class)],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'incurred_on' => ['required', 'date'],
            'resolution_reference' => ['nullable', 'string', 'max:255'],
            'evidence' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }
}
