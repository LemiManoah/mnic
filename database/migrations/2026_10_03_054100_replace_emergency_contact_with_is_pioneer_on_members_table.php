<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->boolean('is_pioneer')->default(false);
            $table->dropColumn('emergency_contact');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('emergency_contact')->nullable();
            $table->dropColumn('is_pioneer');
        });
    }
};
