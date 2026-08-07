<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reconciliations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contribution_period_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('external_account_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('opening_balance');
            // What the external statement actually says, entered by the
            // treasurer from the bank or Mobile Money statement.
            $table->unsignedBigInteger('statement_closing_balance');
            // Derived from verified inflows and verified outflows at the moment
            // the reconciliation is submitted.
            $table->unsignedBigInteger('expected_closing_balance')->default(0);
            $table->bigInteger('difference')->default(0);
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignUuid('prepared_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUuid('confirmed_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['contribution_period_id', 'external_account_id'], 'reconciliations_period_account_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliations');
    }
};
