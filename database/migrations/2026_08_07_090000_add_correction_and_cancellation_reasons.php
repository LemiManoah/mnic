<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table): void {
            $table->text('cancellation_reason')->nullable();
        });

        Schema::table('minutes', function (Blueprint $table): void {
            // A correction supersedes an earlier confirmed version rather than
            // editing it, so both stay in the record and the reason travels
            // with the replacement.
            $table->text('correction_reason')->nullable();
            $table->foreignUuid('corrects_minute_id')->nullable()->constrained('minutes')->nullOnDelete();
        });

        Schema::table('proposals', function (Blueprint $table): void {
            $table->text('withdrawal_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropColumn('cancellation_reason');
        });

        Schema::table('minutes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('corrects_minute_id');
            $table->dropColumn('correction_reason');
        });

        Schema::table('proposals', function (Blueprint $table): void {
            $table->dropColumn('withdrawal_reason');
        });
    }
};
