<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satuan nilai asli kriteria, untuk menampilkan batas sub-kriteria (Rp, %, kg, ...).
        Schema::table('criteria', function (Blueprint $table) {
            $table->string('unit', 10)->nullable()->after('weight');
        });

        // Sub-kriteria: nilai asli >= min_value mendapat rating `rating`.
        // Rating terendah (1) tidak punya batas bawah (min_value null).
        Schema::create('sub_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->decimal('min_value', 15, 4)->nullable();
            $table->timestamps();

            $table->unique(['criteria_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_criteria');

        Schema::table('criteria', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
