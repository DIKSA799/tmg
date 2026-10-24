<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambassadors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_record_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->string('photo_path')->nullable();
            $table->rememberToken();

            $table->foreignId('state_id')->constrained();
            $table->foreignId('lga_id')->constrained();
            $table->foreignId('ward_id')->constrained();
            $table->foreignId('polling_unit_id')->constrained();
            $table->foreignId('senatorial_district_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('notice_seen_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index(['senatorial_district_id', 'id']);
            $table->index('state_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambassadors');
    }
};
