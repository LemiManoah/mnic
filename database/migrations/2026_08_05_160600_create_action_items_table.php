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
        Schema::create('action_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('owner_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->string('status');
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('created_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_items');
    }
};
