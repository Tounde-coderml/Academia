<?php

namespace Tests\Feature;

use App\Models\Epreuve;
use App\Models\EpreuveActivity;
use App\Models\EpreuveFavorite;
use App\Models\Filiere;
use App\Models\FiliereNiveauMatiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_before_opening_catalogue(): void
    {
        $this->get(route('catalogue.index'))
            ->assertRedirectToRoute('login');
    }

    public function test_unverified_user_is_redirected_before_opening_catalogue(): void
    {
        $this->seed(AccessControlSeeder::class);
        $student = User::factory()->unverified()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('catalogue.index'))
            ->assertRedirectToRoute('verification.notice');
    }

    public function test_authenticated_user_without_catalogue_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('catalogue.index'))
            ->assertForbidden();
    }

    public function test_all_four_roles_can_read_the_published_catalogue(): void
    {
        $this->seed(AccessControlSeeder::class);
        $this->createPublishedExam();

        foreach (['etudiant', 'responsable_classe', 'administration', 'administrateur'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)
                ->get(route('catalogue.index'))
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('Catalogue/CatalogueIndex')
                    ->has('epreuves.data', 1));
        }
    }

    public function test_catalogue_and_direct_exam_page_only_expose_published_exams(): void
    {
        $this->seed(AccessControlSeeder::class);
        $association = $this->activeAssociation();
        $published = $this->createPublishedExam($association);
        $pending = $this->createPublishedExam($association, ['status' => 'pending']);
        $rejected = $this->createPublishedExam($association, ['status' => 'rejected']);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('catalogue.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Catalogue/CatalogueIndex')
                ->has('epreuves.data', 1)
                ->where('epreuves.data.0.id', $published->id)
                ->missing('epreuves.data.0.file_path')
                ->missing('epreuves.data.0.file_sha256')
                ->missing('epreuves.data.0.user_id'));

        $this->actingAs($student)->get(route('catalogue.show', $pending))->assertNotFound();
        $this->actingAs($student)->get(route('catalogue.show', $rejected))->assertNotFound();
    }

    public function test_keyword_search_matches_title_subject_and_filiere_without_case_or_accent_sensitivity(): void
    {
        $this->seed(AccessControlSeeder::class);
        $titleMatch = $this->createPublishedExam($this->activeAssociation('Génie logiciel', 'Mathématiques'), [
            'title' => 'Analyse des fonctions',
        ]);
        $subjectMatch = $this->createPublishedExam($this->activeAssociation('Génie logiciel', 'Algèbre linéaire'), [
            'title' => 'Épreuve de mi-session',
        ]);
        $filiereMatch = $this->createPublishedExam($this->activeAssociation('Génie civil', 'Topographie'), [
            'title' => 'Contrôle continu',
        ]);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        foreach ([
            'fonctions' => $titleMatch,
            'algebre' => $subjectMatch,
            'GENIE CIVIL' => $filiereMatch,
        ] as $keyword => $expected) {
            $this->actingAs($student)
                ->get(route('catalogue.index', ['q' => $keyword]))
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('resultCount', 1)
                    ->where('epreuves.data.0.id', $expected->id));
        }
    }

    public function test_keyword_wildcard_characters_are_searched_literally(): void
    {
        $this->seed(AccessControlSeeder::class);
        $matching = $this->createPublishedExam($this->activeAssociation(), ['title' => 'Examen %_ final']);
        $this->createPublishedExam($this->activeAssociation(), ['title' => 'Examen abc final']);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('catalogue.index', ['q' => '%_']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('resultCount', 1)
                ->where('epreuves.data.0.id', $matching->id));
    }

    public function test_all_search_filters_are_applied_together_and_only_published_years_are_offered(): void
    {
        $this->seed(AccessControlSeeder::class);
        $association = $this->activeAssociation('Informatique', 'Algorithmique');
        $expected = $this->createPublishedExam($association, [
            'title' => 'Structures de données',
            'academic_year' => '2026-2027',
            'semester' => 2,
            'type' => 'devoir',
        ]);
        $this->createPublishedExam($association, ['academic_year' => '2025-2026']);
        $this->createPublishedExam($this->activeAssociation('Génie civil', 'Topographie'), [
            'academic_year' => '2026-2027',
            'semester' => 2,
            'type' => 'devoir',
        ]);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('catalogue.index', [
                'q' => 'Structures',
                'filiere_id' => $association->filiere_id,
                'niveau_id' => $association->niveau_id,
                'matiere_id' => $association->matiere_id,
                'semester' => 2,
                'academic_year' => '2026-2027',
                'type' => 'devoir',
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('resultCount', 1)
                ->where('epreuves.data.0.id', $expected->id)
                ->where('filters.academic_year', '2026-2027')
                ->has('options.years', 2));
    }

    public function test_invalid_filters_and_unknown_sort_are_rejected(): void
    {
        $this->seed(AccessControlSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->from(route('catalogue.index'))
            ->get(route('catalogue.index', ['semester' => 3, 'type' => 'unknown', 'sort' => 'email']))
            ->assertRedirect(route('catalogue.index'))
            ->assertSessionHasErrors(['semester', 'type', 'sort']);

        $this->actingAs($student)
            ->from(route('catalogue.index'))
            ->get(route('catalogue.index', ['academic_year' => '2099-2100']))
            ->assertRedirect(route('catalogue.index'))
            ->assertSessionHasErrors('academic_year');
    }

    public function test_catalogue_sorting_is_stable_and_pagination_preserves_search_criteria(): void
    {
        $this->seed(AccessControlSeeder::class);
        $association = $this->activeAssociation('Informatique', 'Algorithmique');
        $older = $this->createPublishedExam($association, ['title' => 'Alpha exam', 'published_at' => now()->subDay()]);
        $newer = $this->createPublishedExam($association, ['title' => 'Beta exam', 'published_at' => now()]);
        Epreuve::factory()->count(19)->create([
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
            'title' => 'Exam pagination',
            'status' => 'published',
            'published_at' => now()->subMinutes(5),
        ]);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('catalogue.index', ['sort' => 'title', 'q' => 'exam']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('epreuves.data.0.id', $older->id)
                ->where('epreuves.data.1.id', $newer->id)
                ->where('epreuves.per_page', 20)
                ->where('resultCount', 21)
                ->has('epreuves.links', 4));
    }

    public function test_filiere_page_shows_unique_active_subjects_only(): void
    {
        $this->seed(AccessControlSeeder::class);
        $filiere = Filiere::factory()->create(['name' => 'Informatique']);
        $firstLevel = Niveau::factory()->create();
        $secondLevel = Niveau::factory()->create();
        $activeSubject = Matiere::factory()->create(['name' => 'Algorithmique']);
        $inactiveSubject = Matiere::factory()->create(['name' => 'Ancienne matière', 'is_active' => false]);
        FiliereNiveauMatiere::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_id' => $firstLevel->id,
            'matiere_id' => $activeSubject->id,
        ]);
        FiliereNiveauMatiere::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_id' => $secondLevel->id,
            'matiere_id' => $activeSubject->id,
        ]);
        FiliereNiveauMatiere::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_id' => $firstLevel->id,
            'matiere_id' => $inactiveSubject->id,
        ]);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('catalogue.filieres.show', $filiere))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Catalogue/FiliereShow')
                ->has('matieres', 1)
                ->where('matieres.0.id', $activeSubject->id));

        $inactiveFiliere = Filiere::factory()->create(['is_active' => false]);
        $this->actingAs($student)->get(route('catalogue.filieres.show', $inactiveFiliere))->assertNotFound();
    }

    public function test_subject_page_shows_published_exams_only(): void
    {
        $this->seed(AccessControlSeeder::class);
        $association = $this->activeAssociation('Informatique', 'Algorithmique');
        $published = $this->createPublishedExam($association);
        $this->createPublishedExam($association, ['status' => 'pending']);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('catalogue.matieres.show', $association->matiere_id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Catalogue/CatalogueIndex')
                ->where('resultCount', 1)
                ->where('epreuves.data.0.id', $published->id));
    }

    public function test_user_can_add_and_remove_a_favorite_without_creating_duplicates(): void
    {
        $this->seed(AccessControlSeeder::class);
        $epreuve = $this->createPublishedExam();
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->put(route('catalogue.favorites.store', $epreuve))
            ->assertRedirect();
        $this->actingAs($student)
            ->put(route('catalogue.favorites.store', $epreuve))
            ->assertRedirect();

        $this->assertDatabaseCount('epreuve_favorites', 1);
        $this->actingAs($student)
            ->get(route('catalogue.show', $epreuve))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('epreuve.is_favorite', true));

        $this->actingAs($student)
            ->delete(route('catalogue.favorites.destroy', $epreuve))
            ->assertRedirect();

        $this->assertDatabaseMissing('epreuve_favorites', [
            'user_id' => $student->id,
            'epreuve_id' => $epreuve->id,
        ]);
    }

    public function test_favorites_and_consultation_history_are_private_to_each_user(): void
    {
        $this->seed(AccessControlSeeder::class);
        $epreuve = $this->createPublishedExam();
        $firstStudent = User::factory()->create();
        $firstStudent->assignRole('etudiant');
        $secondStudent = User::factory()->create();
        $secondStudent->assignRole('etudiant');

        $this->actingAs($firstStudent)->put(route('catalogue.favorites.store', $epreuve));
        $this->actingAs($firstStudent)->get(route('catalogue.show', $epreuve))->assertOk();
        $this->actingAs($secondStudent)
            ->get(route('catalogue.show', $epreuve))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('epreuve.is_favorite', false));

        $this->assertDatabaseHas('epreuve_activities', [
            'user_id' => $firstStudent->id,
            'epreuve_id' => $epreuve->id,
            'action' => 'consultation',
        ]);
        $this->assertDatabaseHas('epreuve_activities', [
            'user_id' => $secondStudent->id,
            'epreuve_id' => $epreuve->id,
            'action' => 'consultation',
        ]);
    }

    public function test_personal_favorites_and_history_only_show_the_users_published_exams(): void
    {
        $this->seed(AccessControlSeeder::class);
        $firstExam = $this->createPublishedExam();
        $otherExam = $this->createPublishedExam();
        $pendingExam = $this->createPublishedExam(null, ['status' => 'pending']);
        $firstStudent = User::factory()->create();
        $firstStudent->assignRole('etudiant');
        $secondStudent = User::factory()->create();
        $secondStudent->assignRole('etudiant');

        EpreuveFavorite::query()->create([
            'user_id' => $firstStudent->id,
            'epreuve_id' => $firstExam->id,
        ]);
        EpreuveFavorite::query()->create([
            'user_id' => $firstStudent->id,
            'epreuve_id' => $pendingExam->id,
        ]);
        EpreuveFavorite::query()->create([
            'user_id' => $secondStudent->id,
            'epreuve_id' => $otherExam->id,
        ]);
        EpreuveActivity::query()->create([
            'user_id' => $firstStudent->id,
            'epreuve_id' => $firstExam->id,
            'action' => 'consultation',
            'occurred_at' => now(),
        ]);
        EpreuveActivity::query()->create([
            'user_id' => $secondStudent->id,
            'epreuve_id' => $otherExam->id,
            'action' => 'consultation',
            'occurred_at' => now(),
        ]);

        $this->actingAs($firstStudent)
            ->get(route('epreuves.favorites'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Personal/PersonalEpreuves')
                ->where('mode', 'favorites')
                ->has('epreuves.data', 1)
                ->where('epreuves.data.0.id', $firstExam->id));

        $this->actingAs($firstStudent)
            ->get(route('epreuves.history'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Personal/PersonalEpreuves')
                ->where('mode', 'history')
                ->has('epreuves.data', 1)
                ->where('epreuves.data.0.id', $firstExam->id)
                ->where('epreuves.data.0.action', 'consultation'));
    }

    public function test_published_exam_supports_safe_inline_preview_and_protected_download(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $association = $this->activeAssociation();
        $filePath = UploadedFile::fake()->create('examen.pdf', 20, 'application/pdf')->store('epreuves', 'local');
        $epreuve = $this->createPublishedExam($association, ['file_path' => $filePath]);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('epreuves.file', ['epreuve' => $epreuve, 'preview' => 1]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="epreuve.pdf"')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeaderContains('Cache-Control', 'private')
            ->assertHeaderContains('Cache-Control', 'no-store');

        $this->actingAs($student)
            ->get(route('epreuves.file', $epreuve))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="epreuve.pdf"');
    }

    public function test_non_previewable_file_can_download_but_cannot_be_rendered_inline(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $association = $this->activeAssociation();
        $filePath = UploadedFile::fake()->create('examen.doc', 20, 'application/msword')->store('epreuves', 'local');
        $epreuve = $this->createPublishedExam($association, [
            'file_path' => $filePath,
            'original_filename' => 'examen.doc',
            'mime_type' => 'application/msword',
        ]);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('epreuves.file', ['epreuve' => $epreuve, 'preview' => 1]))
            ->assertNotFound();

        $this->actingAs($student)
            ->get(route('epreuves.file', $epreuve))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="examen.doc"');
    }

    public function test_only_a_published_file_download_is_added_to_history(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $association = $this->activeAssociation();
        $filePath = UploadedFile::fake()->create('examen.pdf', 20, 'application/pdf')->store('epreuves', 'local');
        $epreuve = $this->createPublishedExam($association, ['file_path' => $filePath]);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('epreuves.file', ['epreuve' => $epreuve, 'preview' => 1]))
            ->assertOk();
        $this->assertDatabaseCount('epreuve_activities', 0);

        $this->actingAs($student)
            ->get(route('epreuves.file', $epreuve))
            ->assertOk();

        $this->assertDatabaseHas('epreuve_activities', [
            'user_id' => $student->id,
            'epreuve_id' => $epreuve->id,
            'action' => 'telechargement',
        ]);
    }

    private function activeAssociation(string $filiereName = 'Informatique', string $matiereName = 'Algèbre'): FiliereNiveauMatiere
    {
        return FiliereNiveauMatiere::factory()->create([
            'filiere_id' => Filiere::factory()->create(['name' => $filiereName])->id,
            'niveau_id' => Niveau::factory()->create()->id,
            'matiere_id' => Matiere::factory()->create(['name' => $matiereName])->id,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function createPublishedExam(?FiliereNiveauMatiere $association = null, array $attributes = []): Epreuve
    {
        $association ??= $this->activeAssociation();

        return Epreuve::factory()->create([
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
            'status' => 'published',
            'published_at' => now(),
            ...$attributes,
        ]);
    }
}
