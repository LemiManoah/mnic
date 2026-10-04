<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('external_reference')->nullable()->unique();
            $table->string('import_key')->nullable()->unique();
        });
        Schema::table('opening_withdrawal_fees', function (Blueprint $table): void {
            $table->string('import_key')->nullable()->unique();
        });
        Schema::create('payment_reference_counters', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_number');
        });

        DB::transaction(function (): void {
            foreach (['payments', 'opening_withdrawal_fees'] as $table) {
                DB::table($table)->select(['id', 'reference'])->chunkById(100, function (Collection $records) use ($table): void {
                    /** @var object{id: string, reference: string} $record */
                    foreach ($records as $record) {
                        $key = $table === 'payments' && ! str_starts_with($record->reference, 'OPENING-') ? 'external_reference' : 'import_key';
                        DB::table($table)->where('id', $record->id)->update([
                            $key => $record->reference,
                            'reference' => 'MIG-'.$record->id,
                        ]);
                    }
                });
            }

            $number = 0;
            foreach (['payments', 'opening_withdrawal_fees'] as $table) {
                DB::table($table)->select('id')->chunkById(100, function (Collection $records) use ($table, &$number): void {
                    /** @var object{id: string} $record */
                    foreach ($records as $record) {
                        DB::table($table)->where('id', $record->id)->update(['reference' => sprintf('MNIC-%06d', ++$number)]);
                    }
                });
            }

            DB::table('payment_reference_counters')->insert(['id' => 1, 'last_number' => $number]);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['external_reference']);
            $table->dropUnique(['import_key']);
            $table->dropColumn(['external_reference', 'import_key']);
        });
        Schema::table('opening_withdrawal_fees', function (Blueprint $table): void {
            $table->dropUnique(['import_key']);
            $table->dropColumn('import_key');
        });
        Schema::dropIfExists('payment_reference_counters');
    }
};
