<?php

use App\Actions\GenerateVoterReference;
use App\Models\VoterRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->string('reference', 32)->nullable()->unique();
        });

        $generator = app(GenerateVoterReference::class);

        VoterRecord::query()
            ->whereNull('reference')
            ->eachById(function (VoterRecord $record) use ($generator): void {
                $record->forceFill([
                    'reference' => $generator->handle(
                        $record->state_id,
                        $record->lga_id,
                        $record->ward_id,
                        $record->polling_unit_id,
                    ),
                ])->save();
            });
    }

    public function down(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn('reference');
        });
    }
};
