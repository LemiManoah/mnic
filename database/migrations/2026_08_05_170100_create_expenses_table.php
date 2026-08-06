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
        Schema::create('expenses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->string('purpose');
            $table->string('category');
            $table->string('payee');
            $table->unsignedBigInteger('amount');
            $table->date('incurred_on');
            $table->string('status');
            // Free text until a resolutions register exists; see mnic.md.
            $table->string('resolution_reference')->nullable();
            $table->foreignUuid('external_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_reference')->nullable();
            $table->date('paid_on')->nullable();

            // Separation of duties: request, approve and verify must be three
            // decisions, and no member may occupy two of the roles on one row.
            $table->foreignUuid('requested_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignUuid('approved_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('verified_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
