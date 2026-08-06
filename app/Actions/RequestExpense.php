<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseEvidence;
use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final readonly class RequestExpense
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(
        array $attributes,
        ?Member $actor = null,
        ?UploadedFile $evidence = null,
        ?string $ipAddress = null,
    ): Expense {
        return DB::transaction(function () use ($attributes, $actor, $evidence, $ipAddress): Expense {
            $expense = Expense::query()->create([
                ...$attributes,
                'status' => ExpenseStatus::Submitted,
                'requested_by_member_id' => $actor?->id,
            ]);

            if ($evidence instanceof UploadedFile) {
                ExpenseEvidence::query()->create([
                    'expense_id' => $expense->id,
                    'path' => (string) $evidence->store('expense-evidence', 'local'),
                    'original_name' => $evidence->getClientOriginalName(),
                    'mime_type' => $evidence->getClientMimeType(),
                    'size' => (int) $evidence->getSize(),
                    'uploaded_by_member_id' => $actor?->id,
                ]);
            }

            $this->recordAuditEvent->handle(
                'expense.requested',
                $expense,
                $actor,
                null,
                $expense->toArray(),
                $ipAddress,
            );

            return $expense;
        });
    }
}
