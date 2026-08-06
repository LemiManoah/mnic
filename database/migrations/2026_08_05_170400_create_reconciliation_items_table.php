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
        // A documented explanation for part of the difference between the
        // system's expected balance and the external statement.
        Schema::create('reconciliation_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('reconciliation_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->bigInteger('amount');
            $table->boolean('is_resolved')->default(false);
            $table->foreignUuid('assigned_to_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_items');
    }
};
