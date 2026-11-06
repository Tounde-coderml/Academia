<?php

namespace Database\Seeders;

use App\Models\Filiere;
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
    }
}
