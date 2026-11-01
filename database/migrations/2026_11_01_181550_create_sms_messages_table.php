<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('status', 16)->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->string('provider_status')->nullable();
            $table->text('error')->nullable();
            $table->string('request_url')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['sms_campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
