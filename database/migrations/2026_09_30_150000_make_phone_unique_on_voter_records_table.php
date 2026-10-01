<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The phone number is the identity of a volunteer record: the same phone must
 * never produce two rows, while a new phone always creates a new row. The
 * client-supplied idempotency key stops being a uniqueness constraint because a
 * reused key (a reloaded tab) must not block a genuinely new registration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->index('idempotency_key');

            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->dropUnique(['phone']);

            $table->dropIndex(['idempotency_key']);
            $table->unique('idempotency_key');
        });
    }
};
