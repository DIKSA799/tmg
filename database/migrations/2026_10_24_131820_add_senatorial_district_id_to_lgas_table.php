<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lgas', function (Blueprint $table) {
            $table->foreignId('senatorial_district_id')
                ->nullable()
                ->after('state_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['state_id', 'senatorial_district_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lgas', function (Blueprint $table) {
            $table->dropIndex(['state_id', 'senatorial_district_id']);
            $table->dropConstrainedForeignId('senatorial_district_id');
        });
    }
};
