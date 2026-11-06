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
        Schema::create('epreuves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('filiere_id')->constrained()->restrictOnDelete();
            $table->foreignId('niveau_id')->constrained()->restrictOnDelete();
            $table->foreignId('matiere_id')->constrained()->restrictOnDelete();
            $table->string('title', 180);
            $table->string('academic_year', 9);
            $table->unsignedTinyInteger('semester');
            $table->string('type', 32);
            $table->string('status', 16)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('published_at')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('file_size');
            $table->char('file_sha256', 64);
            $table->timestamps();
            $table->index(['status', 'submitted_at']);
            $table->index(['matiere_id', 'academic_year', 'type', 'file_sha256'], 'epreuves_duplicate_lookup');
            $table->foreign(['filiere_id', 'niveau_id', 'matiere_id'], 'epreuves_association_fk')
                ->references(['filiere_id', 'niveau_id', 'matiere_id'])
                ->on('filiere_niveau_matiere')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epreuves');
    }
};
