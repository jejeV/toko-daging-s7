<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('value', 15, 4);
            $table->timestamps();

            $table->unique(['product_id', 'criteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_scores');
    }
};

