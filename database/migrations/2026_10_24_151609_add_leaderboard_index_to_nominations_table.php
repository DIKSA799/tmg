<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            // National/admin ranking sorts by status then votes.
            $table->index(['status', 'votes_count'], 'nominations_status_votes_index');
        });
    }

    public function down(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            $table->dropIndex('nominations_status_votes_index');
        });
    }
};
