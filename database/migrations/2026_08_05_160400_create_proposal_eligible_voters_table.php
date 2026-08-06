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
        // A snapshot of exactly who was entitled to vote at the moment voting
        // opened. Suspending or admitting a member afterwards must not change
        // an in-flight or historical vote.
        Schema::create('proposal_eligible_voters', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['proposal_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_eligible_voters');
    }
};
