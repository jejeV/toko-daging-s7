<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('type', 10); // benefit / cost
            $table->decimal('weight', 5, 4);
            $table->timestamps();
        });

        Schema::create('supplier_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('value', 15, 4);
            $table->timestamps();

            $table->unique(['supplier_id', 'criteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_scores');
        Schema::dropIfExists('criteria');
    }
};
