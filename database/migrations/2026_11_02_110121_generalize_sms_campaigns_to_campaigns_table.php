<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generalize the SMS-only tables into channel-aware campaign tables. New
     * tables are created and the old rows copied across rather than renaming in
     * place, which avoids foreign-key rename issues on both MySQL and SQLite.
     *
     * The down() migration drops the generalized tables; it does not recreate the
     * legacy sms_* tables, so a rollback is a forward-fix (documented trade-off).
     */
    public function up(): void
    {
        if (Schema::hasTable('campaigns')) {
            return;
        }

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('termii');
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope_label');
            $table->string('channel', 16)->default('sms');
            $table->string('sender_id', 255)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status', 16)->default('processing');
            $table->boolean('paused')->default(false);
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('campaign_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 16)->default('sms');
            $table->foreignId('voter_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient', 255);
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

            $table->index(['campaign_id', 'status']);
        });

        if (Schema::hasTable('sms_campaigns')) {
            DB::statement(
                'INSERT INTO campaigns (id, provider, admin_id, state_id, lga_id, scope_label, channel, sender_id, subject, message, status, paused, total_recipients, sent_count, failed_count, skipped_count, created_at, updated_at)
                 SELECT id, provider, admin_id, state_id, lga_id, scope_label, channel, sender_id, NULL, message, status, paused, total_recipients, sent_count, failed_count, skipped_count, created_at, updated_at FROM sms_campaigns'
            );
        }

        if (Schema::hasTable('sms_messages')) {
            DB::statement(
                "INSERT INTO campaign_messages (id, campaign_id, channel, voter_record_id, recipient, status, provider_message_id, provider_status, error, request_url, request_headers, request_body, response_status, response_headers, response_body, sent_at, created_at, updated_at)
                 SELECT id, sms_campaign_id, 'sms', voter_record_id, phone, status, provider_message_id, provider_status, error, request_url, request_headers, request_body, response_status, response_headers, response_body, sent_at, created_at, updated_at FROM sms_messages"
            );
        }

        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_campaigns');
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_messages');
        Schema::dropIfExists('campaigns');
    }
};
