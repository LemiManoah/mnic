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
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignUuid('reversed_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['reversed_by_member_id']);
            $table->dropColumn(['reversed_at', 'reversal_reason']);
            $table->dropColumn('reversed_by_member_id');
        });
    }
};
