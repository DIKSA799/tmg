<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('volunteer_category', 64)->nullable();
            $table->string('occupation', 64)->nullable();
            $table->boolean('has_disability')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('voter_records', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'email', 'volunteer_category', 'occupation', 'has_disability']);
        });
    }
};
