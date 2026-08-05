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
        Schema::create('payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            // Portion of a verified payment not yet applied to an obligation,
            // i.e. an advance against future contribution periods.
            $table->unsignedBigInteger('unapplied_amount')->default(0);
            $table->date('paid_on');
            $table->string('method');
            $table->string('reference')->unique();
            $table->text('notes')->nullable();
            $table->string('status');
            $table->foreignUuid('recorded_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignUuid('reviewed_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
