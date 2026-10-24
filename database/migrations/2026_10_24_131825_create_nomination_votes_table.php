<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomination_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nomination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_id')->constrained('ambassadors')->cascadeOnDelete();
            $table->foreignId('senatorial_district_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // One vote per ambassador per senatorial district. This is the
            // database-level guard against duplicate and racing votes.
            $table->unique(['voter_id', 'senatorial_district_id']);
            $table->unique(['nomination_id', 'voter_id']);
            $table->index('nomination_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomination_votes');
    }
};
