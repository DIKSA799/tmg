<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope_label');
            $table->string('channel', 32)->default('sms');
            $table->string('sender_id', 32)->nullable();
            $table->text('message');
            $table->string('status', 16)->default('processing');
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_campaigns');
    }
};
