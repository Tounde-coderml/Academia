<?php

namespace Database\Factories;

use App\Models\Filiere;
use App\Models\FiliereNiveauMatiere;
use App\Models\Matiere;
use App\Models\Niveau;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiliereNiveauMatiere>
 */
class FiliereNiveauMatiereFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filiere_id' => Filiere::factory(),
            'niveau_id' => Niveau::factory(),
            'matiere_id' => Matiere::factory(),
        ];
    }
}
