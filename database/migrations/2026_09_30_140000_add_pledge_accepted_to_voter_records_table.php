<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->boolean('pledge_accepted')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->dropColumn('pledge_accepted');
        });
    }
};
