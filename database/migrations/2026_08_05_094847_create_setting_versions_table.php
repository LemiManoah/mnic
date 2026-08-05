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
        Schema::create('setting_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('setting_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->date('effective_from');
            $table->foreignUuid('created_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();

            $table->index(['setting_id', 'effective_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_versions');
    }
};
