<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomination_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('nominee_id')->constrained('ambassadors')->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('ambassadors')->cascadeOnDelete();
            $table->foreignId('senatorial_district_id')->constrained()->cascadeOnDelete();

            $table->string('message', 280)->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['nominee_id', 'inviter_id']);
            $table->index(['nominee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomination_invitations');
    }
};
