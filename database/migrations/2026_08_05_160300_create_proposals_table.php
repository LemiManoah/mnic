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
        Schema::create('proposals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('status');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            // Eligibility and thresholds are frozen when voting opens, so a
            // later membership or settings change cannot alter the result.
            $table->unsignedInteger('eligible_voter_count')->default(0);
            $table->unsignedInteger('quorum_required')->default(0);
            $table->unsignedTinyInteger('approval_percent')->default(50);
            $table->text('outcome_note')->nullable();
            $table->foreignUuid('created_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
