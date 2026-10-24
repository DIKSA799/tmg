<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nominations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('ambassador_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('state_id')->constrained();
            $table->foreignId('senatorial_district_id')->constrained();
            $table->foreignId('nominated_by')->nullable()->constrained('ambassadors')->nullOnDelete();

            $table->string('statement', 280)->nullable();
            $table->string('status', 16)->default('active');
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['senatorial_district_id', 'status', 'votes_count']);
            $table->index(['state_id', 'status', 'votes_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nominations');
    }
};
