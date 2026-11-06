<?php

namespace Database\Seeders;

use App\Models\Filiere;
use App\Models\FiliereNiveauMatiere;
use App\Models\Matiere;
use App\Models\Niveau;
use Illuminate\Database\Seeder;

class AcademicReferentialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['GI', 'GT', 'GC'] as $code) {
            Filiere::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $code, 'is_active' => true],
            );
        }

        foreach (['L1' => 1, 'L2' => 2, 'L3' => 3] as $code => $sortOrder) {
            Niveau::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $code, 'sort_order' => $sortOrder, 'is_active' => true],
            );
        }

        $gi = Filiere::query()->where('code', 'GI')->firstOrFail();
        $niveaux = Niveau::query()->whereIn('code', ['L1', 'L2', 'L3'])->get()->keyBy('code');
        $catalogue = [
            'ALGO' => ['Algorithmique et programmation', 'L1'],
            'ARCHI' => ['Architecture des ordinateurs', 'L1'],
            'BDD' => ['Bases de données', 'L2'],
            'WEB' => ['Développement web', 'L2'],
            'RESEAUX' => ['Réseaux informatiques', 'L2'],
            'GENLOG' => ['Génie logiciel', 'L3'],
            'CYBER' => ['Cybersécurité', 'L3'],
        ];

        foreach ($catalogue as $code => [$name, $niveauCode]) {
            $matiere = Matiere::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true],
            );

            FiliereNiveauMatiere::query()->firstOrCreate([
                'filiere_id' => $gi->id,
                'niveau_id' => $niveaux->get($niveauCode)->id,
                'matiere_id' => $matiere->id,
            ]);
        }
    }
}
