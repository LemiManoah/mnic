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
        Schema::create('meetings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->string('title');
            $table->dateTime('scheduled_for');
            $table->string('location')->nullable();
            $table->text('agenda')->nullable();
            $table->string('status');
            $table->foreignUuid('scheduled_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
