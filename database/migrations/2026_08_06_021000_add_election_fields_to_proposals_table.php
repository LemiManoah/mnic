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
        Schema::table('proposals', function (Blueprint $table): void {
            $table->string('election_position')->nullable()->after('description');
            $table->foreignUuid('election_member_id')->nullable()->after('election_position')->constrained('members')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            $table->dropForeign(['election_member_id']);
            $table->dropColumn(['election_position', 'election_member_id']);
        });
    }
};
