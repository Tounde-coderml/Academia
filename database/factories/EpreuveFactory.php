<?php

namespace Database\Factories;

use App\Models\Epreuve;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Epreuve>
 */
class EpreuveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'filiere_id' => Filiere::factory(),
            'niveau_id' => Niveau::factory(),
            'matiere_id' => Matiere::factory(),
            'title' => fake()->sentence(4),
            'academic_year' => '2026-2027',
            'semester' => 1,
            'type' => 'examen',
            'status' => 'pending',
            'submitted_at' => now(),
            'file_path' => 'epreuves/'.fake()->uuid().'.pdf',
            'original_filename' => 'epreuve.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'file_sha256' => hash('sha256', fake()->uuid()),
        ];
    }
}
