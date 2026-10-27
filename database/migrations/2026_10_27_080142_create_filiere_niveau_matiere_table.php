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
        Schema::create('filiere_niveau_matiere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filiere_id')->constrained()->restrictOnDelete();
            $table->foreignId('niveau_id')->constrained()->restrictOnDelete();
            $table->foreignId('matiere_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['filiere_id', 'niveau_id', 'matiere_id'],
                'filiere_niveau_matiere_unique',
            );
            $table->index('niveau_id');
            $table->index('matiere_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filiere_niveau_matiere');
    }
};
