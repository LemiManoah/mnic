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
        Schema::create('votes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->string('choice');
            // Declared conflicts of interest are recorded alongside the vote
            // rather than hidden, per the governance rules.
            $table->boolean('has_conflict')->default(false);
            $table->text('conflict_note')->nullable();
            $table->timestamp('cast_at');
            $table->timestamps();

            $table->unique(['proposal_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
