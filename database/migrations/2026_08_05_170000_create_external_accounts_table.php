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
        Schema::create('external_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->string('institution')->nullable();
            // Only the last few digits are stored. The club does not need the
            // full account number to reconcile, so it is never captured.
            $table->string('masked_identifier');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_accounts');
    }
};
