<?php

namespace Tests\Feature;

use App\Models\Epreuve;
use App\Models\Filiere;
use App\Models\FiliereNiveauMatiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\User;
use Database\Seeders\AcademicReferentialsSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class EpreuveTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_submit_an_exam_to_private_storage(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('responsable_classe');
        $association = $this->activeAssociation();

        $this->actingAs($user)
            ->post(route('epreuves.store'), [
                'title' => 'Examen de mathématiques',
                'filiere_id' => $association->filiere_id,
                'niveau_id' => $association->niveau_id,
                'matiere_id' => $association->matiere_id,
                'academic_year' => '2026-2027',
                'semester' => 1,
                'type' => 'examen',
                'file' => UploadedFile::fake()->create('examen.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirectToRoute('epreuves.mine')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('epreuves', [
            'user_id' => $user->id,
            'title' => 'Examen de mathématiques',
            'status' => 'published',
            'semester' => 1,
        ]);
        $epreuve = Epreuve::query()->firstOrFail();
        Storage::disk('local')->assertExists($epreuve->file_path);
        $this->assertStringNotContainsString('file_path', json_encode($epreuve));
    }

    public function test_student_submission_waits_for_reviewer_publication(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('etudiant');
        $association = $this->activeAssociation();

        $this->actingAs($student)
            ->get(route('epreuves.create'))
            ->assertOk();

        $this->actingAs($student)
            ->post(route('epreuves.store'), [
                'title' => 'Devoir étudiant',
                'filiere_id' => $association->filiere_id,
                'niveau_id' => $association->niveau_id,
                'matiere_id' => $association->matiere_id,
                'academic_year' => '2026-2027',
                'semester' => 2,
                'type' => 'devoir',
                'file' => UploadedFile::fake()->create('devoir.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirectToRoute('epreuves.mine');

        $epreuve = Epreuve::query()->firstOrFail();
        $this->assertSame('pending', $epreuve->status);

        $reviewer = User::factory()->create();
        $reviewer->assignRole('responsable_classe');
        $this->actingAs($reviewer)
            ->post(route('epreuves.publish', $epreuve))
            ->assertRedirectToRoute('epreuves.review')
            ->assertSessionHas('status', 'Épreuve publiée.');

        $this->assertDatabaseHas('epreuves', ['id' => $epreuve->id, 'status' => 'published']);
    }

    public function test_submission_form_displays_requested_filiere_and_niveau_codes(): void
    {
        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicReferentialsSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)
            ->get(route('epreuves.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Epreuves/CreateEpreuve')
                ->where('filieres.0.code', 'GI')
                ->where('filieres.1.code', 'GT')
                ->where('filieres.2.code', 'GC')
                ->where('niveaux.0.code', 'L1')
                ->where('niveaux.1.code', 'L2')
                ->where('niveaux.2.code', 'L3'));
    }

    public function test_duplicate_submission_requires_explicit_confirmation_before_direct_publication(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('responsable_classe');
        $association = $this->activeAssociation();
        $data = [
            'title' => 'Examen',
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
            'academic_year' => '2026-2027',
            'semester' => 1,
            'type' => 'examen',
        ];
        $upload = fn (): UploadedFile => UploadedFile::fake()->createWithContent('examen.pdf', '%PDF-1.4 identical exam content');

        $this->actingAs($reviewer)->post(route('epreuves.store'), [...$data, 'file' => $upload()]);

        $this->actingAs($reviewer)
            ->from(route('epreuves.create'))
            ->post(route('epreuves.store'), [...$data, 'file' => $upload()])
            ->assertRedirect(route('epreuves.create'))
            ->assertSessionHasErrors('duplicate');

        $this->assertDatabaseCount('epreuves', 1);
        $this->actingAs($reviewer)
            ->post(route('epreuves.store'), [...$data, 'file' => $upload(), 'duplicate_confirmed' => true])
            ->assertRedirectToRoute('epreuves.mine');

        $this->assertDatabaseCount('epreuves', 2);
        $this->assertDatabaseHas('epreuves', ['title' => 'Examen', 'status' => 'published']);
    }

    public function test_reviewer_must_provide_a_reason_to_reject_an_exam(): void
    {
        $this->seed(AccessControlSeeder::class);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('administrateur');
        $association = $this->activeAssociation();
        $epreuve = Epreuve::factory()->create([
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
        ]);

        $this->actingAs($reviewer)
            ->post(route('epreuves.reject', $epreuve), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($reviewer)
            ->post(route('epreuves.reject', $epreuve), ['reason' => 'Le document est illisible.'])
            ->assertRedirectToRoute('epreuves.review');

        $this->assertDatabaseHas('epreuves', [
            'id' => $epreuve->id,
            'status' => 'rejected',
            'rejection_reason' => 'Le document est illisible.',
        ]);
    }

    public function test_class_manager_can_review_all_student_submissions(): void
    {
        $this->seed(AccessControlSeeder::class);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('responsable_classe');
        $studentOne = User::factory()->create(['name' => 'Étudiant Un']);
        $studentTwo = User::factory()->create(['name' => 'Étudiant Deux']);
        $studentOne->assignRole('etudiant');
        $studentTwo->assignRole('etudiant');
        $association = $this->activeAssociation();
        Epreuve::factory()->create([
            'user_id' => $studentOne->id,
            'submitted_at' => now()->subMinute(),
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
        ]);
        Epreuve::factory()->create([
            'user_id' => $studentTwo->id,
            'submitted_at' => now(),
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
        ]);

        $this->actingAs($reviewer)
            ->get(route('epreuves.review'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Epreuves/ReviewQueue')
                ->has('epreuves.data', 2)
                ->where('epreuves.data.0.author', 'Étudiant Deux')
                ->where('epreuves.data.1.author', 'Étudiant Un'));
    }

    public function test_docx_files_are_rejected(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('etudiant');
        $association = $this->activeAssociation();

        $this->actingAs($student)
            ->from(route('epreuves.create'))
            ->post(route('epreuves.store'), [
                'title' => 'Document non autorisé',
                'filiere_id' => $association->filiere_id,
                'niveau_id' => $association->niveau_id,
                'matiere_id' => $association->matiere_id,
                'academic_year' => '2026-2027',
                'semester' => 1,
                'type' => 'devoir',
                'file' => UploadedFile::fake()->create(
                    'devoir.docx',
                    100,
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ),
            ])
            ->assertRedirect(route('epreuves.create'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('epreuves', 0);
    }

    public function test_file_size_limit_accepts_20_mb_and_rejects_larger_files(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('etudiant');
        $association = $this->activeAssociation();
        $attributes = [
            'title' => 'Épreuve de taille maximale',
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
            'academic_year' => '2026-2027',
            'semester' => 1,
            'type' => 'examen',
        ];

        $this->actingAs($student)
            ->from(route('epreuves.create'))
            ->post(route('epreuves.store'), [
                ...$attributes,
                'file' => UploadedFile::fake()->create('trop-grand.pdf', 20481, 'application/pdf'),
            ])
            ->assertRedirect(route('epreuves.create'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('epreuves', 0);

        $this->actingAs($student)
            ->post(route('epreuves.store'), [
                ...$attributes,
                'title' => 'Épreuve de 20 Mo',
                'file' => UploadedFile::fake()->create('limite.pdf', 20480, 'application/pdf'),
            ])
            ->assertRedirectToRoute('epreuves.mine');

        $this->assertDatabaseHas('epreuves', [
            'title' => 'Épreuve de 20 Mo',
            'file_size' => 20 * 1024 * 1024,
        ]);
    }

    public function test_students_cannot_open_the_review_queue(): void
    {
        $this->seed(AccessControlSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('etudiant');

        $this->actingAs($student)->get(route('epreuves.review'))->assertForbidden();
    }

    public function test_only_administrator_can_edit_and_delete_recorded_exams(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole('administrateur');
        $manager = User::factory()->create();
        $manager->assignRole('responsable_classe');
        $association = $this->activeAssociation();
        $filePath = UploadedFile::fake()->create('admin.pdf', 20, 'application/pdf')->store('epreuves', 'local');
        $epreuve = Epreuve::factory()->create([
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
            'file_path' => $filePath,
        ]);

        $this->actingAs($manager)->get(route('admin.epreuves.index'))->assertForbidden();
        $this->actingAs($administrator)
            ->get(route('admin.epreuves.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Epreuves/AdminIndex'));
        $this->actingAs($administrator)
            ->put(route('admin.epreuves.update', $epreuve), [
                'title' => 'Titre corrigé',
                'filiere_id' => $association->filiere_id,
                'niveau_id' => $association->niveau_id,
                'matiere_id' => $association->matiere_id,
                'academic_year' => '2026-2027',
                'semester' => 1,
                'type' => 'examen',
            ])
            ->assertRedirectToRoute('admin.epreuves.index');

        $this->assertDatabaseHas('epreuves', ['id' => $epreuve->id, 'title' => 'Titre corrigé']);
        $this->actingAs($administrator)
            ->delete(route('admin.epreuves.destroy', $epreuve))
            ->assertRedirectToRoute('admin.epreuves.index');

        $this->assertDatabaseMissing('epreuves', ['id' => $epreuve->id]);
        Storage::disk('local')->assertMissing($filePath);
    }

    public function test_user_can_only_download_own_pending_exam(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $owner = User::factory()->create();
        $owner->assignRole('responsable_classe');
        $other = User::factory()->create();
        $other->assignRole('etudiant');
        $association = $this->activeAssociation();
        $filePath = UploadedFile::fake()->create('examen.pdf', 10, 'application/pdf')->store('epreuves', 'local');
        $epreuve = Epreuve::query()->create([
            'user_id' => $owner->id,
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
            'title' => 'Examen privé',
            'academic_year' => '2026-2027',
            'semester' => 1,
            'type' => 'examen',
            'status' => 'pending',
            'submitted_at' => now(),
            'file_path' => $filePath,
            'original_filename' => 'examen.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10240,
            'file_sha256' => hash('sha256', 'exam'),
        ]);

        $this->actingAs($other)->get(route('epreuves.file', $epreuve))->assertNotFound();
        $this->actingAs($owner)->get(route('epreuves.file', $epreuve))->assertOk();
    }

    public function test_any_student_with_published_exam_permission_can_download_published_exam(): void
    {
        Storage::fake('local');
        $this->seed(AccessControlSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('etudiant');
        $association = $this->activeAssociation();
        $filePath = UploadedFile::fake()->create('examen.pdf', 10, 'application/pdf')->store('epreuves', 'local');
        $epreuve = Epreuve::factory()->create([
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
            'status' => 'published',
            'published_at' => now(),
            'file_path' => $filePath,
            'original_filename' => 'examen.pdf',
        ]);

        $this->actingAs($student)
            ->get(route('epreuves.file', $epreuve))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="examen.pdf"');
    }

    public function test_owner_can_see_their_submission_status(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('responsable_classe');
        $association = $this->activeAssociation();
        Epreuve::factory()->create([
            'user_id' => $user->id,
            'filiere_id' => $association->filiere_id,
            'niveau_id' => $association->niveau_id,
            'matiere_id' => $association->matiere_id,
        ]);

        $this->actingAs($user)
            ->get(route('epreuves.mine'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Epreuves/MyEpreuves')
                ->has('epreuves.data', 1)
                ->missing('epreuves.data.0.file_path'));
    }

    private function activeAssociation(): FiliereNiveauMatiere
    {
        $filiere = Filiere::factory()->create();
        $niveau = Niveau::factory()->create();
        $matiere = Matiere::factory()->create();

        return FiliereNiveauMatiere::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'matiere_id' => $matiere->id,
        ]);
    }
}
