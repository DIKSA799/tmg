<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lga_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('code', 10);
            $table->timestamps();

            $table->unique(['lga_id', 'code']);
            $table->index(['lga_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
