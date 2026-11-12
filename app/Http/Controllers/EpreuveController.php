<?php

namespace App\Http\Controllers;

use App\Events\EpreuveTelechargee;
use App\Http\Requests\StoreEpreuveRequest;
use App\Http\Requests\UpdateEpreuveRequest;
use App\Models\Epreuve;
use App\Models\Filiere;
use App\Models\FiliereNiveauMatiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Semestre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EpreuveController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $epreuves = Epreuve::query()
            ->whereBelongsTo($request->user())
            ->with(['filiere:id,name', 'niveau:id,name', 'matiere:id,name'])
            ->latest('submitted_at')
            ->paginate(15)
            ->through(fn (Epreuve $epreuve): array => [
                'id' => $epreuve->id,
                'title' => $epreuve->title,
                'filiere' => $epreuve->filiere->name,
                'niveau' => $epreuve->niveau->name,
                'matiere' => $epreuve->matiere->name,
                'academic_year' => $epreuve->academic_year,
                'semester' => 'S'.$epreuve->semester,
                'type' => $epreuve->type,
                'status' => $epreuve->status,
                'submitted_at' => $epreuve->submitted_at->toDateTimeString(),
                'rejection_reason' => $epreuve->rejection_reason,
            ]);

        return Inertia::render('Epreuves/MyEpreuves', [
            'epreuves' => $epreuves,
            'canReview' => $request->user()->can('exams.review'),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Epreuves/CreateEpreuve', [
            'filieres' => Filiere::query()
                ->where('is_active', true)
                ->get(['id', 'code', 'name'])
                ->sortBy(fn (Filiere $filiere): int => match ($filiere->code) {
                    'GI' => 0,
                    'GT' => 1,
                    'GC' => 2,
                    default => 3,
                })
                ->values(),
            'niveaux' => Niveau::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
            'matieres' => Matiere::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'associations' => FiliereNiveauMatiere::query()->whereHas('filiere', fn ($query) => $query->where('is_active', true))
                ->whereHas('niveau', fn ($query) => $query->where('is_active', true))
                ->whereHas('matiere', fn ($query) => $query->where('is_active', true))
                ->get(['filiere_id', 'niveau_id', 'matiere_id']),
            'semestres' => Semestre::options(),
            'types' => [
                ['id' => 'examen', 'name' => 'Examen'],
                ['id' => 'rattrapage', 'name' => 'Rattrapage'],
                ['id' => 'devoir', 'name' => 'Devoir'],
                ['id' => 'controle_continu', 'name' => 'Contrôle continu'],
            ],
            'academicYears' => $this->academicYears(),
        ]);
    }

    public function reviewIndex(): InertiaResponse
    {
        $epreuves = Epreuve::query()
            ->where('status', 'pending')
            ->with(['user:id,name', 'filiere:id,name', 'niveau:id,name', 'matiere:id,name'])
            ->latest('submitted_at')
            ->paginate(20)
            ->through(fn (Epreuve $epreuve): array => [
                'id' => $epreuve->id,
                'title' => $epreuve->title,
                'author' => $epreuve->user->name,
                'filiere' => $epreuve->filiere->name,
                'niveau' => $epreuve->niveau->name,
                'matiere' => $epreuve->matiere->name,
                'academic_year' => $epreuve->academic_year,
                'semester' => 'S'.$epreuve->semester,
                'type' => $epreuve->type,
                'submitted_at' => $epreuve->submitted_at->toDateTimeString(),
                'has_duplicate' => Epreuve::query()
                    ->where('id', '<>', $epreuve->id)
                    ->where('matiere_id', $epreuve->matiere_id)
                    ->where('academic_year', $epreuve->academic_year)
                    ->where('type', $epreuve->type)
                    ->where('file_sha256', $epreuve->file_sha256)
                    ->exists(),
            ]);

        return Inertia::render('Epreuves/ReviewQueue', ['epreuves' => $epreuves]);
    }

    public function adminIndex(): InertiaResponse
    {
        $epreuves = Epreuve::query()
            ->with(['user:id,name', 'filiere:id,name', 'niveau:id,name', 'matiere:id,name'])
            ->latest('submitted_at')
            ->paginate(20)
            ->through(fn (Epreuve $epreuve): array => [
                'id' => $epreuve->id,
                'title' => $epreuve->title,
                'author' => $epreuve->user->name,
                'filiere' => $epreuve->filiere->name,
                'niveau' => $epreuve->niveau->name,
                'matiere' => $epreuve->matiere->name,
                'academic_year' => $epreuve->academic_year,
                'semester' => 'S'.$epreuve->semester,
                'type' => $epreuve->type,
                'status' => $epreuve->status,
            ]);

        return Inertia::render('Epreuves/AdminIndex', ['epreuves' => $epreuves]);
    }

    public function edit(Epreuve $epreuve): InertiaResponse
    {
        return Inertia::render('Epreuves/CreateEpreuve', [
            'filieres' => Filiere::query()
                ->where('is_active', true)
                ->get(['id', 'code', 'name'])
                ->sortBy(fn (Filiere $filiere): int => match ($filiere->code) {
                    'GI' => 0,
                    'GT' => 1,
                    'GC' => 2,
                    default => 3,
                })
                ->values(),
            'niveaux' => Niveau::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
            'matieres' => Matiere::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'associations' => FiliereNiveauMatiere::query()->get(['filiere_id', 'niveau_id', 'matiere_id']),
            'semestres' => Semestre::options(),
            'types' => [
                ['id' => 'examen', 'name' => 'Examen'],
                ['id' => 'rattrapage', 'name' => 'Rattrapage'],
                ['id' => 'devoir', 'name' => 'Devoir'],
                ['id' => 'controle_continu', 'name' => 'Contrôle continu'],
            ],
            'academicYears' => array_values(array_unique([$epreuve->academic_year, ...$this->academicYears()])),
            'epreuve' => $epreuve->only([
                'id', 'title', 'filiere_id', 'niveau_id', 'matiere_id', 'academic_year', 'semester', 'type',
            ]),
        ]);
    }

    public function update(UpdateEpreuveRequest $request, Epreuve $epreuve): RedirectResponse
    {
        $attributes = $request->safe()->except(['file']);
        $file = $request->file('file');
        $oldPath = $epreuve->file_path;
        $newPath = null;

        if ($file !== null) {
            $newPath = $file->store('epreuves', $this->epreuvesDisk());
            if ($newPath === false) {
                return back()->withErrors(['file' => 'Le fichier n’a pas pu être enregistré.']);
            }

            $attributes['file_path'] = $newPath;
            $attributes['original_filename'] = basename($file->getClientOriginalName());
            $attributes['mime_type'] = $file->getMimeType() ?: 'application/octet-stream';
            $attributes['file_size'] = $file->getSize();
            $attributes['file_sha256'] = hash_file('sha256', $file->getRealPath());
        }

        try {
            $epreuve->update($attributes);
        } catch (\Throwable $exception) {
            if ($newPath !== null) {
                Storage::disk($this->epreuvesDisk())->delete($newPath);
            }
            throw $exception;
        }

        if ($newPath !== null) {
            Storage::disk($this->epreuvesDisk())->delete($oldPath);
        }

        return to_route('admin.epreuves.index')->with('status', 'Épreuve modifiée.');
    }

    public function destroy(Epreuve $epreuve): RedirectResponse
    {
        $path = $epreuve->file_path;
        $epreuve->delete();
        Storage::disk($this->epreuvesDisk())->delete($path);

        return to_route('admin.epreuves.index')->with('status', 'Épreuve supprimée.');
    }

    public function store(StoreEpreuveRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store('epreuves', $this->epreuvesDisk());

        if ($path === false) {
            return back()->withErrors(['file' => 'Le fichier n’a pas pu être enregistré.']);
        }

        try {
            $attributes = $request->safe()->except(['file', 'duplicate_confirmed']);
            $isDuplicate = Epreuve::query()
                ->where('matiere_id', $request->integer('matiere_id'))
                ->where('academic_year', $request->string('academic_year')->toString())
                ->where('type', $request->string('type')->toString())
                ->where('file_sha256', hash_file('sha256', $file->getRealPath()))
                ->exists();
            $canPublishDirectly = $request->user()->can('exams.submit')
                && ! $request->user()->hasRole('etudiant');

            if ($isDuplicate && $canPublishDirectly && ! $request->boolean('duplicate_confirmed')) {
                Storage::disk($this->epreuvesDisk())->delete($path);
                throw ValidationException::withMessages([
                    'duplicate' => 'Une épreuve identique existe. Confirme que tu veux poursuivre avant de la publier.',
                ]);
            }

            $attributes['user_id'] = $request->user()->id;
            $attributes['status'] = $canPublishDirectly ? 'published' : 'pending';
            $attributes['submitted_at'] = now();
            $attributes['published_at'] = $attributes['status'] === 'published' ? now() : null;
            $attributes['file_path'] = $path;
            $attributes['original_filename'] = basename($file->getClientOriginalName());
            $attributes['mime_type'] = $file->getMimeType() ?: 'application/octet-stream';
            $attributes['file_size'] = $file->getSize();
            $attributes['file_sha256'] = hash_file('sha256', $file->getRealPath());

            Epreuve::create($attributes);
        } catch (\Throwable $exception) {
            Storage::disk($this->epreuvesDisk())->delete($path);
            throw $exception;
        }

        $message = $attributes['status'] === 'published'
            ? 'Épreuve déposée et publiée.'
            : 'Épreuve déposée et envoyée en attente de validation.';

        return to_route('epreuves.mine')->with('status', $message);
    }

    public function publish(Request $request, Epreuve $epreuve): RedirectResponse
    {
        abort_unless($epreuve->status === 'pending', 404);

        $isDuplicate = Epreuve::query()
            ->where('id', '<>', $epreuve->id)
            ->where('matiere_id', $epreuve->matiere_id)
            ->where('academic_year', $epreuve->academic_year)
            ->where('type', $epreuve->type)
            ->where('file_sha256', $epreuve->file_sha256)
            ->exists();

        if ($isDuplicate && ! $request->boolean('duplicate_confirmed')) {
            throw ValidationException::withMessages([
                'duplicate' => 'Une épreuve identique existe. Confirme que tu veux publier ce doublon.',
            ]);
        }

        $epreuve->update(['status' => 'published', 'published_at' => now(), 'rejection_reason' => null]);

        return to_route('epreuves.review')->with('status', 'Épreuve publiée.');
    }

    public function reject(Request $request, Epreuve $epreuve): RedirectResponse
    {
        abort_unless($epreuve->status === 'pending', 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $epreuve->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

        return to_route('epreuves.review')->with('status', 'Épreuve refusée.');
    }

    public function file(Request $request, Epreuve $epreuve): StreamedResponse
    {
        abort_unless(
            $epreuve->status === 'published'
                || $epreuve->user_id === $request->user()->id
                || $request->user()->can('exams.review'),
            404,
        );
        $disk = Storage::disk($this->epreuvesDisk());
        abort_unless($disk->exists($epreuve->file_path), 404);
        $downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($epreuve->original_filename)) ?: 'epreuve';
        $isPreview = $request->boolean('preview');
        $previewableMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];

        abort_if($isPreview && ! in_array($epreuve->mime_type, $previewableMimeTypes, true), 404);

        if (! $isPreview && $epreuve->status === 'published') {
            $userId = (int) $request->user()->id;
            $epreuveId = $epreuve->id;
            defer(fn () => EpreuveTelechargee::dispatch($userId, $epreuveId));
        }

        $response = $disk->response($epreuve->file_path, $downloadName, [
            'Content-Type' => $epreuve->mime_type,
            'Content-Disposition' => ($isPreview ? 'inline' : 'attachment').'; filename="'.$downloadName.'"',
            'Content-Length' => (string) $epreuve->file_size,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);

        return $response->setPrivate();
    }

    private function epreuvesDisk(): string
    {
        return (string) config('filesystems.epreuves_disk', 'local');
    }

    /** @return list<string> */
    private function academicYears(): array
    {
        $year = (int) now()->format('Y');
        $start = now()->month >= 9 ? $year : $year - 1;

        return array_map(fn (int $offset): string => ($start - $offset).'-'.($start - $offset + 1), range(0, 4));
    }
}
