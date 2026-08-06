<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Reconciliation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by the submit, confirm and lock transitions; the ability check differs
 * per route and is supplied by the `ability` route default.
 */
final class ReviewReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reconciliation = $this->route('reconciliation');
        assert($reconciliation instanceof Reconciliation);

        $ability = $this->route()?->defaults['ability'] ?? 'update';
        assert(is_string($ability));

        return (bool) $this->user()?->can($ability, $reconciliation);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
