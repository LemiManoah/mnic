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
        Schema::create('contribution_periods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            // Snapshot of the club contribution amount when the period opened,
            // so later settings changes never rewrite historical obligations.
            $table->unsignedBigInteger('amount');
            $table->date('due_date');
            $table->date('grace_ends_on');
            $table->string('status');
            $table->foreignUuid('opened_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_periods');
    }
};
