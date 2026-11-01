<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->string('provider', 32)->default('termii')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('sms_campaigns', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
