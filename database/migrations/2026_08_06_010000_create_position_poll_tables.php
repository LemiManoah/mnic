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
        Schema::create('position_polls', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('position');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            // Snapshotted when voting opens so amending club settings later
            // cannot retroactively change this election's outcome.
            $table->unsignedInteger('eligible_voter_count')->default(0);
            $table->unsignedInteger('quorum_required')->default(0);
            // Constrained below, once the candidates table it points at exists.
            $table->uuid('winning_candidate_id')->nullable();
            $table->text('outcome_note')->nullable();
            $table->foreignUuid('created_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();

            $table->index(['position', 'status']);
        });

        Schema::create('position_poll_candidates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('position_poll_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('nominated_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->text('manifesto')->nullable();
            $table->timestamps();

            // Standing twice in the same election would split a candidate's own
            // vote and break the plurality count.
            $table->unique(['position_poll_id', 'member_id']);
        });

        Schema::create('position_poll_eligible_voters', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('position_poll_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['position_poll_id', 'member_id']);
        });

        Schema::create('position_poll_votes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('position_poll_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('position_poll_candidate_id')->constrained()->cascadeOnDelete();
            $table->timestamp('cast_at');
            $table->timestamps();

            // One member, one vote.
            $table->unique(['position_poll_id', 'member_id']);
        });

        Schema::table('position_polls', function (Blueprint $table): void {
            $table->foreign('winning_candidate_id')
                ->references('id')
                ->on('position_poll_candidates')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('position_polls', function (Blueprint $table): void {
            $table->dropForeign(['winning_candidate_id']);
        });

        Schema::dropIfExists('position_poll_votes');
        Schema::dropIfExists('position_poll_eligible_voters');
        Schema::dropIfExists('position_poll_candidates');
        Schema::dropIfExists('position_polls');
    }
};
