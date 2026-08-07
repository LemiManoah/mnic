<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('position_holdings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->string('position');
            $table->date('held_from');
            $table->date('held_to')->nullable();
            $table->foreignUuid('elected_via_proposal_id')->nullable()->constrained('proposals')->nullOnDelete();
            $table->foreignUuid('appointed_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->text('transfer_reason')->nullable();
            $table->timestamps();

            $table->index(['position', 'held_to']);
            $table->index(['member_id', 'held_to']);
        });

        DB::table('members')
            ->whereNotNull('position')
            ->orderBy('created_at')
            ->get(['id', 'position', 'joined_at', 'created_at', 'updated_at'])
            ->each(function (object $member): void {
                DB::table('position_holdings')->insert([
                    'id' => (string) Str::uuid(),
                    'member_id' => $member->id,
                    'position' => $member->position,
                    'held_from' => $member->joined_at ?? now()->toDateString(),
                    'held_to' => null,
                    'elected_via_proposal_id' => null,
                    'appointed_by_member_id' => null,
                    'transfer_reason' => 'Migrated from member profile position.',
                    'created_at' => $member->created_at ?? now(),
                    'updated_at' => $member->updated_at ?? now(),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_holdings');
    }
};
