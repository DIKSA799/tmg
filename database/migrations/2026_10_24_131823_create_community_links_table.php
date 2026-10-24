<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 16);
            $table->string('scope_type', 16)->default('global');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('label')->nullable();
            $table->string('url');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['platform', 'scope_type', 'scope_id']);
            $table->index(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_links');
    }
};
