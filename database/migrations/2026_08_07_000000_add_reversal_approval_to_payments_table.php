<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reversing a verified payment now takes two officers: one to ask and a
     * different one to approve. These columns record the request half; the
     * existing reversed_by/reversed_at columns record the approval.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignUuid('reversal_requested_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('reversal_requested_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['reversal_requested_by_member_id']);
            $table->dropColumn(['reversal_requested_by_member_id', 'reversal_requested_at']);
        });
    }
};
