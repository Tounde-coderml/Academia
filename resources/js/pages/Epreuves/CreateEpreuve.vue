<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";

interface Option {
    id: number | string;
    name: string;
    code?: string;
}

interface Association {
    filiere_id: number;
    niveau_id: number;
    matiere_id: number;
}

interface Epreuve {
    id: number;
    title: string;
    filiere_id: number;
    niveau_id: number;
    matiere_id: number;
    academic_year: string;
    semester: number;
    type: string;
}

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

const availableMatieres = () =>
    props.associations
        .filter(
            (association) =>
                association.filiere_id === Number(form.filiere_id) &&
                association.niveau_id === Number(form.niveau_id),
        )
        .map((association) => association.matiere_id);

function submit(): void {
    if (props.epreuve) {
        form.put(`/admin/epreuves/${props.epreuve.id}`, {
            forceFormData: true,
        });
        return;
    }

    form.post("/epreuves", { forceFormData: true });
}

function duplicateError(): string | undefined {
    return (form.errors as Record<string, string>).duplicate;
}
</script>

<template>
    <Head
        :title="props.epreuve ? 'Modifier une épreuve' : 'Déposer une épreuve'"
    />

    <main class="min-h-svh bg-slate-50 text-slate-900">
        <div
            class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12"
        >
            <header class="border-b border-slate-300 pb-7">
                <Link
                    :href="props.epreuve ? '/admin/epreuves' : '/mes-epreuves'"
                    class="text-sm font-semibold text-indigo-800 underline decoration-indigo-300 underline-offset-4 hover:text-indigo-950"
                >
                    {{
                        props.epreuve
                            ? "Gestion des épreuves"
                            : "Retour à mes épreuves"
                    }}
                </Link>
                <p
                    class="mt-6 text-xs font-bold uppercase tracking-[0.18em] text-indigo-800"
                >
                    Espace étudiant · Module 04
                </p>
                <h1
                    class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"
                >
                    {{
                        props.epreuve
                            ? "Modifier une épreuve"
                            : "Déposer une épreuve"
                    }}
                </h1>
                <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                    Renseigne les informations du cours, puis ajoute le document
                    afin de le soumettre à la bibliothèque.
                </p>
            </header>

            <form
                class="overflow-hidden rounded-3xl border border-slate-300 bg-white shadow-sm"
                @submit.prevent="submit"
            >
                <section
                    class="border-b border-slate-300 px-5 py-7 sm:px-8 sm:py-9"
                >
                    <div class="mb-7 max-w-2xl">
                        <h2 class="text-xl font-bold text-slate-950">
                            Informations de l’épreuve
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Les champs marqués d’un astérisque sont
                            obligatoires. Choisis la filière et le niveau pour
                            afficher les matières associées.
                        </p>
                    </div>

                    <div class="grid gap-x-6 gap-y-6 md:grid-cols-2">
                        <label class="block space-y-2 md:col-span-2">
                            <span class="text-sm font-bold text-slate-800"
                                >Titre
                                <span class="text-rose-700" aria-hidden="true"
                                    >*</span
                                ></span
                            >
                            <input
                                v-model="form.title"
                                maxlength="180"
                                required
                                :aria-invalid="Boolean(form.errors.title)"
                                class="min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 text-base text-slate-950 shadow-sm transition placeholder:text-slate-500 focus:border-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                placeholder="Ex. Examen d’algorithmique — session normale"
                            />
                            <span
                                v-if="form.errors.title"
                                class="block text-sm font-semibold text-rose-800"
                                >{{ form.errors.title }}</span
                            >
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-bold text-slate-800"
                                >Filière
                                <span class="text-rose-700" aria-hidden="true"
                                    >*</span
                                ></span
                            >
                            <select
                                v-model="form.filiere_id"
                                required
                                :aria-invalid="Boolean(form.errors.filiere_id)"
                                class="min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 text-base text-slate-950 shadow-sm transition focus:border-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                <option
                                    v-for="option in filieres"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.code ?? option.name }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.filiere_id"
                                class="block text-sm font-semibold text-rose-800"
                                >{{ form.errors.filiere_id }}</span
                            >
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-bold text-slate-800"
                                >Niveau
                                <span class="text-rose-700" aria-hidden="true"
                                    >*</span
                                ></span
                            >
                            <select
                                v-model="form.niveau_id"
                                required
                                :aria-invalid="Boolean(form.errors.niveau_id)"
                                class="min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 text-base text-slate-950 shadow-sm transition focus:border-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                <option
                                    v-for="option in niveaux"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.code ?? option.name }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.niveau_id"
                                class="block text-sm font-semibold text-rose-800"
                                >{{ form.errors.niveau_id }}</span
                            >
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-bold text-slate-800"
                                >Matière
                                <span class="text-rose-700" aria-hidden="true"
                                    >*</span
                                ></span
                            >
                            <select
                                v-model="form.matiere_id"
                                required
                                :aria-invalid="Boolean(form.errors.matiere_id)"
                                class="min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 text-base text-slate-950 shadow-sm transition focus:border-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                <option value="">Choisir une matière</option>
                                <option
                                    v-for="option in matieres.filter((item) =>
                                        availableMatieres().includes(
                                            Number(item.id),
                                        ),
                                    )"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.matiere_id"
                                class="block text-sm font-semibold text-rose-800"
                                >{{ form.errors.matiere_id }}</span
                            >
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-bold text-slate-800"
                                >Année scolaire
                                <span class="text-rose-700" aria-hidden="true"
                                    >*</span
                                ></span
                            >
                            <select
                                v-model="form.academic_year"
                                required
                                :aria-invalid="
                                    Boolean(form.errors.academic_year)
                                "
                                class="min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 text-base text-slate-950 shadow-sm transition focus:border-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                <option
                                    v-for="year in academicYears"
                                    :key="year"
                                    :value="year"
                                >
                                    {{ year }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.academic_year"
                                class="block text-sm font-semibold text-rose-800"
                                >{{ form.errors.academic_year }}</span
                            >
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-bold text-slate-800"
                                >Semestre
                                <span class="text-rose-700" aria-hidden="true"
                                    >*</span
                                ></span
                            >
                            <select
                                v-model="form.semester"
                                required
                                :aria-invalid="Boolean(form.errors.semester)"
                                class="min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 text-base text-slate-950 shadow-sm transition focus:border-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                <option
                                    v-for="option in semestres"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.semester"
                                class="block text-sm font-semibold text-rose-800"
                                >{{ form.errors.semester }}</span
                            >
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-bold text-slate-800"
                                >Type d’épreuve
                                <span class="text-rose-700" aria-hidden="true"
                                    >*</span
                                ></span
                            >
                            <select
                                v-model="form.type"
                                required
                                :aria-invalid="Boolean(form.errors.type)"
                                class="min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 text-base text-slate-950 shadow-sm transition focus:border-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                                <option
                                    v-for="option in types"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.type"
                                class="block text-sm font-semibold text-rose-800"
                                >{{ form.errors.type }}</span
                            >
                        </label>
                    </div>
                </section>

                <section class="px-5 py-7 sm:px-8 sm:py-9">
                    <div class="mb-5">
                        <h2 class="text-xl font-bold text-slate-950">
                            Document à joindre
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Formats acceptés : PDF, DOC, JPG ou PNG. Taille
                            maximale : 20 Mo. DOCX n’est pas accepté.
                            <span v-if="props.epreuve"
                                >Tu peux laisser ce champ vide pour conserver le
                                fichier actuel.</span
                            >
                        </p>
                    </div>

                    <label
                        class="block rounded-2xl border-2 border-dashed border-slate-400 bg-slate-50 p-5 transition hover:border-indigo-500 hover:bg-indigo-50/40 sm:p-7"
                    >
                        <span
                            class="mb-3 block text-sm font-bold text-slate-800"
                            >Fichier
                            <span
                                v-if="!props.epreuve"
                                class="text-rose-700"
                                aria-hidden="true"
                                >*</span
                            ></span
                        >
                        <input
                            type="file"
                            accept=".pdf,.doc,.jpg,.jpeg,.png"
                            :required="!props.epreuve"
                            :aria-invalid="Boolean(form.errors.file)"
                            class="block min-h-12 w-full cursor-pointer rounded-xl border border-slate-300 bg-white text-sm text-slate-800 shadow-sm file:mr-4 file:min-h-12 file:border-0 file:bg-indigo-950 file:px-4 file:font-bold file:text-white hover:file:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                            @input="
                                form.file =
                                    ($event.target as HTMLInputElement)
                                        .files?.[0] ?? null
                            "
                        />
                        <span
                            v-if="form.errors.file"
                            class="mt-3 block text-sm font-semibold text-rose-800"
                            >{{ form.errors.file }}</span
                        >
                    </label>

                    <div
                        v-if="duplicateError()"
                        class="mt-6 rounded-2xl border-2 border-amber-400 bg-amber-50 p-5 text-sm text-amber-950 shadow-sm sm:p-6"
                        role="alert"
                    >
                        <p class="font-semibold leading-6">
                            {{ duplicateError() }}
                        </p>
                        <label
                            class="mt-4 flex items-start gap-3 rounded-xl border border-amber-300 bg-white/70 p-4 leading-6"
                        >
                            <input
                                v-model="form.duplicate_confirmed"
                                class="mt-1 size-4 rounded border-amber-600 text-amber-800 focus:ring-amber-600"
                                type="checkbox"
                            />
                            <span
                                >Je confirme le dépôt malgré la présence de ce
                                doublon.</span
                            >
                        </label>
                        <button
                            v-if="form.duplicate_confirmed"
                            class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-900 bg-amber-900 px-5 font-bold text-white hover:bg-amber-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-800"
                            type="button"
                            @click="submit"
                        >
                            Confirmer le dépôt
                        </button>
                    </div>
                </section>

                <footer
                    class="flex flex-col-reverse gap-3 border-t border-slate-300 bg-slate-100/70 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8"
                >
                    <Link
                        :href="
                            props.epreuve ? '/admin/epreuves' : '/mes-epreuves'
                        "
                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-400 bg-white px-5 text-sm font-semibold text-slate-800 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    >
                        Annuler
                    </Link>
                    <button
                        class="inline-flex min-h-12 items-center justify-center rounded-xl border border-indigo-950 bg-indigo-950 px-7 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:cursor-wait disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? "Envoi en cours…"
                                : props.epreuve
                                  ? "Enregistrer les modifications"
                                  : "Déposer l’épreuve"
                        }}
                    </button>
                </footer>
            </form>
        </div>
    </main>
</template>
