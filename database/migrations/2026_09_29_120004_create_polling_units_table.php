<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polling_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32)->unique();
            $table->string('pu_code', 10);
            $table->timestamps();

            $table->index(['ward_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_units');
    }
};
