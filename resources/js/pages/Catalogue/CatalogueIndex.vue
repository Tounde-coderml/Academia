<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

interface NamedOption {
    id: number | string;
    name: string;
}

interface Epreuve {
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
    is_favorite: boolean;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    title: string;
    action: string;
    epreuves: Pagination<Epreuve>;
    filters: Record<string, string | number>;
    resultCount: number;
    options: {
        filieres: NamedOption[];
        niveaux: NamedOption[];
        matieres: NamedOption[];
        years: string[];
        semesters: NamedOption[];
        types: NamedOption[];
    };
}>();

const page = usePage<{ errors?: Record<string, string> }>();
const validationMessages = computed(() =>
    Object.values(page.props.errors ?? {}),
);

function paginationLabel(label: string): string {
    return label
        .replace(/<[^>]*>/g, "")
        .replaceAll("&laquo;", "«")
        .replaceAll("&raquo;", "»");
}

function typeLabel(type: string): string {
    return type.replaceAll("_", " ");
}

function toggleFavorite(epreuve: Epreuve): void {
    const url = `/epreuves/${epreuve.id}/favori`;

    if (epreuve.is_favorite) {
        router.delete(url, { preserveScroll: true });
    } else {
        router.put(url, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="title" />

    <main class="min-h-svh bg-slate-50 text-slate-900">
        <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
            <header
                class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-300 pb-6"
            >
                <div>
                    <Link
                        href="/dashboard"
                        class="text-sm font-semibold text-indigo-800 underline underline-offset-4"
                        >Accueil</Link
                    >
                    <h1
                        class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"
                    >
                        {{ title }}
                    </h1>
                    <p
                        class="mt-3 max-w-2xl text-base leading-7 text-slate-600"
                    >
                        Recherche parmi les épreuves publiées, puis filtre les
                        résultats selon ton parcours.
                    </p>
                </div>
                <p
                    class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700"
                    aria-live="polite"
                >
                    {{ resultCount }} résultat{{ resultCount === 1 ? "" : "s" }}
                </p>
            </header>

            <form
                :action="action"
                method="get"
                class="grid gap-4 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4"
            >
                <div
                    v-if="validationMessages.length > 0"
                    role="alert"
                    class="sm:col-span-2 lg:col-span-4 rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-950"
                >
                    <p class="font-bold">
                        Certains critères ne sont pas valides.
                    </p>
                    <ul class="mt-1 list-disc pl-5">
                        <li
                            v-for="(message, index) in validationMessages"
                            :key="index"
                        >
                            {{ message }}
                        </li>
                    </ul>
                </div>
                <label class="sm:col-span-2 lg:col-span-4">
                    <span
                        class="mb-1.5 block text-sm font-semibold text-slate-800"
                        >Mot-clé</span
                    >
                    <input
                        name="q"
                        type="search"
                        maxlength="120"
                        :value="filters.q"
                        placeholder="Titre, matière ou filière"
                        class="min-h-12 w-full rounded-xl border border-slate-400 px-4 text-base focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    />
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-sm font-semibold text-slate-800"
                        >Filière</span
                    >
                    <select
                        name="filiere_id"
                        :value="filters.filiere_id"
                        class="min-h-12 w-full rounded-xl border border-slate-400 bg-white px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    >
                        <option value="">Toutes les filières</option>
                        <option
                            v-for="option in options.filieres"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }}
                        </option>
                    </select>
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-sm font-semibold text-slate-800"
                        >Niveau</span
                    >
                    <select
                        name="niveau_id"
                        :value="filters.niveau_id"
                        class="min-h-12 w-full rounded-xl border border-slate-400 bg-white px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    >
                        <option value="">Tous les niveaux</option>
                        <option
                            v-for="option in options.niveaux"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }}
                        </option>
                    </select>
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-sm font-semibold text-slate-800"
                        >Matière</span
                    >
                    <select
                        name="matiere_id"
                        :value="filters.matiere_id"
                        class="min-h-12 w-full rounded-xl border border-slate-400 bg-white px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    >
                        <option value="">Toutes les matières</option>
                        <option
                            v-for="option in options.matieres"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }}
                        </option>
                    </select>
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-sm font-semibold text-slate-800"
                        >Semestre</span
                    >
                    <select
                        name="semester"
                        :value="filters.semester"
                        class="min-h-12 w-full rounded-xl border border-slate-400 bg-white px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    >
                        <option value="">Tous les semestres</option>
                        <option
                            v-for="option in options.semesters"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }}
                        </option>
                    </select>
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-sm font-semibold text-slate-800"
                        >Année scolaire</span
                    >
                    <select
                        name="academic_year"
                        :value="filters.academic_year"
                        class="min-h-12 w-full rounded-xl border border-slate-400 bg-white px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    >
                        <option value="">Toutes les années</option>
                        <option
                            v-if="options.years.length === 0"
                            value=""
                            disabled
                        >
                            Aucune année disponible
                        </option>
                        <option
                            v-for="year in options.years"
                            :key="year"
                            :value="year"
                        >
                            {{ year }}
                        </option>
                    </select>
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-sm font-semibold text-slate-800"
                        >Type</span
                    >
                    <select
                        name="type"
                        :value="filters.type"
                        class="min-h-12 w-full rounded-xl border border-slate-400 bg-white px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    >
                        <option value="">Tous les types</option>
                        <option
                            v-for="option in options.types"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }}
                        </option>
                    </select>
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-sm font-semibold text-slate-800"
                        >Trier par</span
                    >
                    <select
                        name="sort"
                        :value="filters.sort"
                        class="min-h-12 w-full rounded-xl border border-slate-400 bg-white px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    >
                        <option value="recent">Plus récentes</option>
                        <option value="year">Année scolaire</option>
                        <option value="title">Titre</option>
                        <option value="subject">Matière</option>
                    </select>
                </label>
                <div
                    class="flex flex-wrap items-center gap-3 sm:col-span-2 lg:col-span-4"
                >
                    <button
                        type="submit"
                        class="min-h-12 rounded-xl bg-indigo-800 px-6 text-sm font-bold text-white hover:bg-indigo-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800"
                    >
                        Rechercher
                    </button>
                    <Link
                        href="/epreuves"
                        class="inline-flex min-h-12 items-center rounded-xl border border-slate-400 bg-white px-5 text-sm font-semibold text-slate-800 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                        >Réinitialiser</Link
                    >
                </div>
            </form>

            <section aria-label="Résultats du catalogue" class="space-y-4">
                <article
                    v-for="epreuve in epreuves.data"
                    :key="epreuve.id"
                    class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm transition hover:border-indigo-400 hover:shadow-md sm:p-6"
                >
                    <div
                        class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-xs font-bold uppercase tracking-wide text-indigo-800"
                            >
                                <Link
                                    :href="`/matieres/${epreuve.matiere_id}/epreuves`"
                                    class="underline underline-offset-2"
                                    >{{ epreuve.matiere }}</Link
                                >
                                ·
                                <Link
                                    :href="`/filieres/${epreuve.filiere_id}/epreuves`"
                                    class="underline underline-offset-2"
                                    >{{ epreuve.filiere }}</Link
                                >
                                · {{ epreuve.niveau }}
                            </p>
                            <h2
                                class="mt-2 break-words text-xl font-bold text-slate-950"
                            >
                                {{ epreuve.title }}
                            </h2>
                            <p class="mt-3 text-sm text-slate-600">
                                {{ epreuve.academic_year }} ·
                                {{ epreuve.semester }} ·
                                <span class="capitalize">{{
                                    typeLabel(epreuve.type)
                                }}</span>
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button
                                type="button"
                                :aria-pressed="epreuve.is_favorite"
                                :aria-label="
                                    epreuve.is_favorite
                                        ? 'Retirer des favoris'
                                        : 'Ajouter aux favoris'
                                "
                                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-400 px-4 text-sm font-bold text-amber-950 hover:bg-amber-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700"
                                @click="toggleFavorite(epreuve)"
                            >
                                {{
                                    epreuve.is_favorite
                                        ? "★ Favori"
                                        : "☆ Ajouter aux favoris"
                                }}
                            </button>
                            <Link
                                :href="`/epreuves/${epreuve.id}`"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 px-5 text-sm font-bold text-indigo-900 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                                >Consulter l’épreuve</Link
                            >
                        </div>
                    </div>
                </article>
                <div
                    v-if="epreuves.data.length === 0"
                    class="rounded-2xl border border-dashed border-slate-400 bg-white px-6 py-12 text-center"
                >
                    <h2 class="text-lg font-bold text-slate-950">
                        {{
                            options.years.length === 0
                                ? "Aucune épreuve publiée pour le moment"
                                : "Aucune épreuve trouvée"
                        }}
                    </h2>
                    <p class="mt-2 text-sm text-slate-600">
                        {{
                            options.years.length === 0
                                ? "Les épreuves apparaîtront ici après leur validation et leur publication."
                                : "Essaie d’autres mots-clés ou retire un ou plusieurs filtres."
                        }}
                    </p>
                </div>
            </section>

            <nav
                v-if="epreuves.links.length > 3"
                aria-label="Pages de résultats"
                class="flex flex-wrap justify-center gap-2"
            >
                <template v-for="(link, index) in epreuves.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :aria-current="link.active ? 'page' : undefined"
                        class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border px-3 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                        :class="
                            link.active
                                ? 'border-indigo-800 bg-indigo-800 text-white'
                                : 'border-slate-400 bg-white text-slate-800 hover:bg-slate-100'
                        "
                        >{{ paginationLabel(link.label) }}</Link
                    >
                    <span
                        v-else
                        class="inline-flex min-h-11 items-center rounded-lg border border-slate-200 px-3 text-sm text-slate-400"
                        >{{ paginationLabel(link.label) }}</span
                    >
                </template>
            </nav>
        </div>
    </main>
</template>
