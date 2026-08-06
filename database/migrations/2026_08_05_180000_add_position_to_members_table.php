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
        Schema::table('members', function (Blueprint $table): void {
            // The elected office a member holds, if any. Separate from their
            // permission role — see App\Enums\ClubPosition.
            // No ->after(): SQLite ignores it, so the column would land in a
            // different place per driver and the model's toArray test would
            // only pass on one of them.
            $table->string('position')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn('position');
        });
    }
};
