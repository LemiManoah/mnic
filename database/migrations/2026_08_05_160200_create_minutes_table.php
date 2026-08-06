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
        Schema::create('minutes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('meeting_id')->constrained()->cascadeOnDelete();
            // Minutes are version controlled: publishing again adds a row
            // rather than editing the previous one.
            $table->unsignedInteger('version');
            $table->text('body');
            $table->foreignUuid('published_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignUuid('confirmed_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();

            $table->unique(['meeting_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minutes');
    }
};
