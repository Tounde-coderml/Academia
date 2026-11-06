<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";

interface Option { id: number | string; name: string; code?: string }
interface Association { filiere_id: number; niveau_id: number; matiere_id: number }
interface Epreuve { id: number; title: string; filiere_id: number; niveau_id: number; matiere_id: number; academic_year: string; semester: number; type: string }

const props = defineProps<{
    filieres: Option[];
    niveaux: Option[];
    matieres: Option[];
    associations: Association[];
    semestres: { id: number; name: string }[];
    types: Option[];
    academicYears: string[];
    epreuve?: Epreuve | null;
}>();

const form = useForm({
    title: props.epreuve?.title ?? "",
    filiere_id: props.epreuve?.filiere_id ?? props.filieres[0]?.id ?? "",
    niveau_id: props.epreuve?.niveau_id ?? props.niveaux[0]?.id ?? "",
    matiere_id: props.epreuve?.matiere_id ?? "",
    academic_year: props.epreuve?.academic_year ?? props.academicYears[0] ?? "",
    semester: props.epreuve?.semester ?? 1,
    type: props.epreuve?.type ?? "examen",
    file: null as File | null,
    duplicate_confirmed: false,
});

const availableMatieres = () => props.associations
    .filter((association) => association.filiere_id === Number(form.filiere_id)
        && association.niveau_id === Number(form.niveau_id))
    .map((association) => association.matiere_id);

function submit(): void {
    if (props.epreuve) {
        form.put(`/admin/epreuves/${props.epreuve.id}`, { forceFormData: true });
        return;
    }
    form.post("/epreuves", { forceFormData: true });
}

function duplicateError(): string | undefined {
    return (form.errors as Record<string, string>).duplicate;
}
</script>

<template>
    <Head title="Déposer une épreuve" />
    <main class="mx-auto max-w-3xl space-y-6 px-4 py-10">
        <div>
            <Link :href="props.epreuve ? '/admin/epreuves' : '/mes-epreuves'" class="text-sm text-indigo-700">{{ props.epreuve ? 'Gestion des épreuves' : 'Mes épreuves' }}</Link>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ props.epreuve ? 'Modifier une épreuve' : 'Déposer une épreuve' }}</h1>
            <p class="mt-1 text-sm text-slate-600">PDF, DOC ou photo (JPG/PNG) — 20 Mo maximum. DOCX n’est pas accepté.</p>
        </div>
        <form class="space-y-5 rounded-xl border border-slate-200 bg-white p-6" @submit.prevent="submit">
            <label class="block text-sm font-medium">Titre
                <input v-model="form.title" class="mt-1 w-full rounded-md border-slate-300" maxlength="180" required>
                <span v-if="form.errors.title" class="text-sm text-red-700">{{ form.errors.title }}</span>
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium">Filière
                    <select v-model="form.filiere_id" class="mt-1 w-full rounded-md border-slate-300" required>
                        <option v-for="option in filieres" :key="option.id" :value="option.id">{{ option.code ?? option.name }}</option>
                    </select>
                </label>
                <label class="text-sm font-medium">Niveau
                    <select v-model="form.niveau_id" class="mt-1 w-full rounded-md border-slate-300" required>
                        <option v-for="option in niveaux" :key="option.id" :value="option.id">{{ option.code ?? option.name }}</option>
                    </select>
                </label>
                <label class="text-sm font-medium">Matière
                    <select v-model="form.matiere_id" class="mt-1 w-full rounded-md border-slate-300" required>
                        <option value="">Choisir</option>
                        <option v-for="option in matieres.filter((item) => availableMatieres().includes(Number(item.id)))" :key="option.id" :value="option.id">{{ option.name }}</option>
                    </select>
                    <span v-if="form.errors.matiere_id" class="text-sm text-red-700">{{ form.errors.matiere_id }}</span>
                </label>
                <label class="text-sm font-medium">Année scolaire
                    <select v-model="form.academic_year" class="mt-1 w-full rounded-md border-slate-300" required>
                        <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
                    </select>
                </label>
                <label class="text-sm font-medium">Semestre
                    <select v-model="form.semester" class="mt-1 w-full rounded-md border-slate-300" required>
                        <option v-for="option in semestres" :key="option.id" :value="option.id">{{ option.name }}</option>
                    </select>
                </label>
                <label class="text-sm font-medium">Type
                    <select v-model="form.type" class="mt-1 w-full rounded-md border-slate-300" required>
                        <option v-for="option in types" :key="option.id" :value="option.id">{{ option.name }}</option>
                    </select>
                </label>
            </div>
            <label class="block text-sm font-medium">Fichier
                <input class="mt-1 block w-full text-sm" type="file" accept=".pdf,.doc,.jpg,.jpeg,.png" :required="!props.epreuve" @input="form.file = ($event.target as HTMLInputElement).files?.[0] ?? null">
                <span v-if="form.errors.file" class="text-sm text-red-700">{{ form.errors.file }}</span>
            </label>
            <div v-if="duplicateError()" class="rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                <p>{{ duplicateError() }}</p>
                <label class="mt-3 flex items-center gap-2"><input v-model="form.duplicate_confirmed" type="checkbox"> Je confirme le dépôt malgré le doublon.</label>
                <button v-if="form.duplicate_confirmed" class="mt-3 rounded-md bg-amber-800 px-4 py-2 font-semibold text-white" type="button" @click="submit">Confirmer le dépôt</button>
            </div>
            <button class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" :disabled="form.processing">{{ form.processing ? "Envoi…" : props.epreuve ? "Enregistrer" : "Déposer" }}</button>
        </form>
    </main>
</template>
