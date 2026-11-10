<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps<{
    epreuve: {
        id: number;
        title: string;
        filiere: string;
        filiere_id: number;
        niveau: string;
        matiere: string;
        matiere_id: number;
        academic_year: string;
        semester: string;
        type: string;
        published_at: string | null;
        file_size: number;
        can_preview: boolean;
        is_favorite: boolean;
    };
}>();

function formatSize(bytes: number): string {
    return `${(bytes / (1024 * 1024)).toFixed(2)} Mo`;
}

function toggleFavorite(): void {
    const url = `/epreuves/${props.epreuve.id}/favori`;

    if (props.epreuve.is_favorite) {
        router.delete(url, { preserveScroll: true });
    } else {
        router.put(url, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="epreuve.title" />

    <main class="min-h-svh bg-slate-50 text-slate-900">
        <div class="mx-auto max-w-5xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
            <header class="border-b border-slate-300 pb-6">
                <Link
                    href="/epreuves"
                    class="text-sm font-semibold text-indigo-800 underline underline-offset-4"
                    >Retour au catalogue</Link
                >
                <p
                    class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-indigo-800"
                >
                    Fiche d’épreuve
                </p>
                <h1
                    class="mt-2 break-words text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"
                >
                    {{ epreuve.title }}
                </h1>
                <p class="mt-3 text-base text-slate-600">
                    {{ epreuve.matiere }} · {{ epreuve.filiere }} ·
                    {{ epreuve.niveau }}
                </p>
                <button
                    type="button"
                    :aria-pressed="epreuve.is_favorite"
                    :aria-label="
                        epreuve.is_favorite
                            ? 'Retirer des favoris'
                            : 'Ajouter aux favoris'
                    "
                    class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-400 px-4 text-sm font-bold text-amber-950 hover:bg-amber-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700"
                    @click="toggleFavorite"
                >
                    {{
                        epreuve.is_favorite
                            ? "★ Favori"
                            : "☆ Ajouter aux favoris"
                    }}
                </button>
            </header>

            <section
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                aria-label="Informations de l’épreuve"
            >
                <div class="rounded-2xl border border-slate-300 bg-white p-5">
                    <p
                        class="text-xs font-bold uppercase tracking-wide text-slate-500"
                    >
                        Année scolaire
                    </p>
                    <p class="mt-2 font-semibold text-slate-950">
                        {{ epreuve.academic_year }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-300 bg-white p-5">
                    <p
                        class="text-xs font-bold uppercase tracking-wide text-slate-500"
                    >
                        Semestre
                    </p>
                    <p class="mt-2 font-semibold text-slate-950">
                        {{ epreuve.semester }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-300 bg-white p-5">
                    <p
                        class="text-xs font-bold uppercase tracking-wide text-slate-500"
                    >
                        Type
                    </p>
                    <p class="mt-2 font-semibold capitalize text-slate-950">
                        {{ epreuve.type.replaceAll("_", " ") }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-300 bg-white p-5">
                    <p
                        class="text-xs font-bold uppercase tracking-wide text-slate-500"
                    >
                        Taille du fichier
                    </p>
                    <p class="mt-2 font-semibold text-slate-950">
                        {{ formatSize(epreuve.file_size) }}
                    </p>
                </div>
            </section>

            <section
                class="space-y-4 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm sm:p-7"
                aria-labelledby="document-title"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2
                            id="document-title"
                            class="text-xl font-bold text-slate-950"
                        >
                            Document de l’épreuve
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Le fichier est servi par l’application avec contrôle
                            d’accès.
                        </p>
                    </div>
                    <a
                        :href="`/epreuves/${epreuve.id}/fichier`"
                        class="inline-flex min-h-12 items-center justify-center rounded-xl bg-indigo-800 px-5 text-sm font-bold text-white hover:bg-indigo-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800"
                        >Télécharger le fichier</a
                    >
                </div>
                <iframe
                    v-if="epreuve.can_preview"
                    :src="`/epreuves/${epreuve.id}/fichier?preview=1`"
                    :title="`Aperçu du document : ${epreuve.title}`"
                    class="h-[70vh] min-h-96 w-full rounded-xl border border-slate-300 bg-slate-100"
                ></iframe>
                <p
                    v-else
                    class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950"
                >
                    L’aperçu en ligne n’est pas disponible pour ce format.
                    Télécharge le document pour le consulter.
                </p>
            </section>

            <nav
                class="flex flex-wrap gap-3"
                aria-label="Navigation pédagogique"
            >
                <Link
                    :href="`/filieres/${epreuve.filiere_id}/epreuves`"
                    class="rounded-xl border border-slate-400 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100"
                    >Autres épreuves de {{ epreuve.filiere }}</Link
                >
                <Link
                    :href="`/matieres/${epreuve.matiere_id}/epreuves`"
                    class="rounded-xl border border-slate-400 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100"
                    >Autres épreuves de {{ epreuve.matiere }}</Link
                >
            </nav>
        </div>
    </main>
</template>
