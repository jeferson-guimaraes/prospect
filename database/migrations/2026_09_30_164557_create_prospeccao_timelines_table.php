<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospeccao_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospeccao_id')->constrained('prospeccao')->onDelete('cascade');
            $table->text('observacao');
            $table->dateTime('data_observacao')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospeccao_timelines');
    }
};
