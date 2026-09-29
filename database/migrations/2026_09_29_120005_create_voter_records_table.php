<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voter_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('idempotency_key', 64)->unique();

            $table->string('full_name');
            $table->string('gender', 32);
            $table->string('age_band', 16);
            $table->string('phone', 20);

            $table->foreignId('state_id')->constrained();
            $table->foreignId('lga_id')->constrained();
            $table->foreignId('ward_id')->constrained();
            $table->foreignId('polling_unit_id')->constrained();

            $table->string('registered_voter_status', 16);
            $table->string('pvc_status', 32);
            $table->string('preferred_language', 32);
            $table->string('preferred_language_other')->nullable();
            $table->string('preferred_channel', 32);
            $table->string('preferred_channel_other')->nullable();

            $table->boolean('consent_to_contact');
            $table->boolean('consent_to_data');

            $table->string('agent_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('device')->nullable();

            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['state_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_records');
    }
};
