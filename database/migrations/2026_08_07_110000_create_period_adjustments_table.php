<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_adjustments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contribution_period_id')->constrained()->cascadeOnDelete();
            // Signed: negative corrects an overstatement, positive an
            // understatement. Whole UGX, like every other amount here.
            $table->integer('amount');
            $table->text('reason');
            $table->string('status');
            $table->foreignUuid('requested_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->foreignUuid('reviewed_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_adjustments');
    }
};
