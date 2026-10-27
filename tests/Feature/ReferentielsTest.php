<?php

namespace Tests\Feature;

use App\Models\Filiere;
use App\Models\FiliereNiveauMatiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReferentielsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_referential_with_a_trimmed_code(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->post(route('admin.filieres.store'), [
                'code' => '  INFO  ',
                'name' => 'Informatique',
            ])
            ->assertRedirectToRoute('admin.filieres.index')
            ->assertSessionHas('status', 'Filière créée.');

        $this->assertDatabaseHas('filieres', [
            'code' => 'INFO',
            'name' => 'Informatique',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_codes_are_rejected_after_trimming(): void
    {
        $administrator = $this->administrator();
        Filiere::factory()->create(['code' => 'INFO']);

        $this->actingAs($administrator)
            ->from(route('admin.filieres.create'))
            ->post(route('admin.filieres.store'), [
                'code' => ' INFO ',
                'name' => 'Informatique',
            ])
            ->assertRedirect(route('admin.filieres.create'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('filieres', 1);
    }

    public function test_database_rejects_duplicate_referential_codes(): void
    {
        Filiere::factory()->create(['code' => 'INFO']);

        $this->expectException(QueryException::class);

        Filiere::factory()->create(['code' => 'INFO']);
    }

    public function test_guest_is_redirected_to_login_before_accessing_referential_management(): void
    {
        $this->get(route('admin.filieres.index'))
            ->assertRedirectToRoute('login');

        $this->post(route('admin.filieres.store'), [
            'code' => 'INFO',
            'name' => 'Informatique',
        ])->assertRedirectToRoute('login');

        $this->assertDatabaseCount('filieres', 0);
    }

    public function test_guest_is_redirected_to_login_before_reading_active_referentials(): void
    {
        $this->get(route('referentiels.actifs'))
            ->assertRedirectToRoute('login');
    }

    public function test_user_without_management_permission_is_forbidden_from_the_admin_area(): void
    {
        $this->seed(AccessControlSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('admin.filieres.index'))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('admin.filieres.store'), [
                'code' => 'INFO',
                'name' => 'Informatique',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('filieres', 0);
    }

    public function test_authenticated_users_receive_only_active_referentials_with_public_fields(): void
    {
        $student = User::factory()->create();
        $activeFiliere = Filiere::factory()->create([
            'code' => 'INFO',
            'name' => 'Informatique',
        ]);
        $inactiveFiliere = Filiere::factory()->create([
            'code' => 'OLD',
            'name' => 'Ancienne filière',
            'is_active' => false,
        ]);
        $niveau = Niveau::factory()->create(['code' => 'L1']);
        $matiere = Matiere::factory()->create(['code' => 'MATH']);
        FiliereNiveauMatiere::factory()->create([
            'filiere_id' => $activeFiliere->id,
            'niveau_id' => $niveau->id,
            'matiere_id' => $matiere->id,
        ]);
        FiliereNiveauMatiere::factory()->create([
            'filiere_id' => $inactiveFiliere->id,
            'niveau_id' => $niveau->id,
            'matiere_id' => $matiere->id,
        ]);

        $this->actingAs($student)
            ->get(route('referentiels.actifs'))
            ->assertOk()
            ->assertExactJson([
                'filieres' => [[
                    'id' => $activeFiliere->id,
                    'code' => 'INFO',
                    'name' => 'Informatique',
                ]],
                'niveaux' => [[
                    'id' => $niveau->id,
                    'code' => 'L1',
                    'name' => $niveau->name,
                    'sort_order' => $niveau->sort_order,
                ]],
                'matieres' => [[
                    'id' => $matiere->id,
                    'code' => 'MATH',
                    'name' => $matiere->name,
                ]],
                'associations' => [[
                    'filiere_id' => $activeFiliere->id,
                    'niveau_id' => $niveau->id,
                    'matiere_id' => $matiere->id,
                ]],
            ]);
    }

    public function test_admin_lists_inactive_values_for_management(): void
    {
        $administrator = $this->administrator();
        $inactiveFiliere = Filiere::factory()->create([
            'code' => 'OLD',
            'name' => 'Ancienne filière',
            'is_active' => false,
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.filieres.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Referentiels/ReferentielsIndex')
                ->where('items.data.0.id', $inactiveFiliere->id)
                ->where('items.data.0.is_active', false)
                ->missing('items.data.0.password'));
    }

    public function test_used_referentials_are_disabled_instead_of_deleted_and_their_codes_are_locked(): void
    {
        $administrator = $this->administrator();
        $filiere = Filiere::factory()->create(['code' => 'INFO']);
        $niveau = Niveau::factory()->create(['code' => 'L1']);
        $matiere = Matiere::factory()->create(['code' => 'MATH']);
        FiliereNiveauMatiere::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'matiere_id' => $matiere->id,
        ]);

        $this->actingAs($administrator)
            ->put(route('admin.matieres.update', $matiere), [
                'code' => 'MATH-NEW',
                'name' => $matiere->name,
            ])
            ->assertSessionHasErrors([
                'code' => 'Le code ne peut plus être modifié après utilisation.',
            ]);

        $this->actingAs($administrator)
            ->delete(route('admin.filieres.destroy', $filiere))
            ->assertSessionHas('status', 'Cette filière est utilisée; elle a été désactivée.');

        $this->assertDatabaseHas('filieres', [
            'id' => $filiere->id,
            'code' => 'INFO',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('filiere_niveau_matiere', [
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'matiere_id' => $matiere->id,
        ]);
    }

    public function test_unused_referentials_can_be_physically_deleted(): void
    {
        $administrator = $this->administrator();
        $filiere = Filiere::factory()->create();

        $this->actingAs($administrator)
            ->delete(route('admin.filieres.destroy', $filiere))
            ->assertSessionHas('status', 'Filière supprimée.');

        $this->assertDatabaseMissing('filieres', ['id' => $filiere->id]);
    }

    public function test_deactivated_referential_is_hidden_from_active_values_and_can_be_reactivated(): void
    {
        $administrator = $this->administrator();
        $filiere = Filiere::factory()->create(['code' => 'INFO']);

        $this->actingAs($administrator)
            ->post(route('admin.filieres.deactivate', $filiere))
            ->assertSessionHas('status', 'Filière désactivée.');

        $this->actingAs($administrator)
            ->get(route('referentiels.actifs'))
            ->assertOk()
            ->assertJsonPath('filieres', []);

        $this->actingAs($administrator)
            ->post(route('admin.filieres.reactivate', $filiere))
            ->assertSessionHas('status', 'Filière réactivée.');

        $this->actingAs($administrator)
            ->get(route('referentiels.actifs'))
            ->assertOk()
            ->assertJsonPath('filieres.0.code', 'INFO');
    }

    public function test_association_requires_active_values_and_rejects_a_duplicate_triplet(): void
    {
        $administrator = $this->administrator();
        $filiere = Filiere::factory()->create();
        $niveau = Niveau::factory()->create();
        $matiere = Matiere::factory()->create();
        $payload = ['filiere_id' => $filiere->id, 'niveau_id' => $niveau->id];

        $this->actingAs($administrator)
            ->post(route('admin.matieres.associations.store', $matiere), $payload)
            ->assertRedirect()
            ->assertSessionHas('status', 'Association ajoutée.');

        $this->actingAs($administrator)
            ->post(route('admin.matieres.associations.store', $matiere), $payload)
            ->assertSessionHasErrors('filiere_id');

        $this->assertDatabaseCount('filiere_niveau_matiere', 1);

        $inactiveNiveau = Niveau::factory()->create(['is_active' => false]);

        $this->actingAs($administrator)
            ->post(route('admin.matieres.associations.store', $matiere), [
                'filiere_id' => $filiere->id,
                'niveau_id' => $inactiveNiveau->id,
            ])
            ->assertSessionHasErrors('niveau_id');

        $this->assertDatabaseCount('filiere_niveau_matiere', 1);
    }

    public function test_database_rejects_duplicate_association_triplets(): void
    {
        $filiere = Filiere::factory()->create();
        $niveau = Niveau::factory()->create();
        $matiere = Matiere::factory()->create();
        $association = [
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'matiere_id' => $matiere->id,
        ];
        FiliereNiveauMatiere::factory()->create($association);

        $this->expectException(QueryException::class);

        FiliereNiveauMatiere::factory()->create($association);
    }

    public function test_administrator_can_remove_an_association_without_deleting_its_referentials(): void
    {
        $administrator = $this->administrator();
        $filiere = Filiere::factory()->create();
        $niveau = Niveau::factory()->create();
        $matiere = Matiere::factory()->create();
        $association = FiliereNiveauMatiere::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'matiere_id' => $matiere->id,
        ]);

        $this->actingAs($administrator)
            ->delete(route('admin.matieres.associations.destroy', [$matiere, $association]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Association supprimée.');

        $this->assertDatabaseMissing('filiere_niveau_matiere', ['id' => $association->id]);
        $this->assertDatabaseHas('filieres', ['id' => $filiere->id]);
        $this->assertDatabaseHas('niveaux', ['id' => $niveau->id]);
        $this->assertDatabaseHas('matieres', ['id' => $matiere->id]);
    }

    private function administrator(): User
    {
        $this->seed(AccessControlSeeder::class);

        $administrator = User::factory()->create();
        $administrator->assignRole('administrateur');

        return $administrator;
    }
}
