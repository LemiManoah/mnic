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
        Schema::table('member_obligations', function (Blueprint $table): void {
            $table->foreignUuid('adjusted_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('adjusted_at')->nullable();
            $table->text('adjustment_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_obligations', function (Blueprint $table): void {
            $table->dropForeign(['adjusted_by_member_id']);
            $table->dropColumn(['adjusted_by_member_id', 'adjusted_at', 'adjustment_reason']);
        });
    }
};
