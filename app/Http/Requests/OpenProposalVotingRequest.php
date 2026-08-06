<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Proposal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class OpenProposalVotingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');
        assert($proposal instanceof Proposal);

        return (bool) $this->user()?->can('manageVoting', $proposal);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'closes_at' => ['required', 'date', 'after:now'],
        ];
    }
}
