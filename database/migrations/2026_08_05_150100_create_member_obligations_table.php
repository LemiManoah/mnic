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
        Schema::create('member_obligations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contribution_period_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->string('status');
            $table->timestamps();

            $table->unique(['contribution_period_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_obligations');
    }
};
