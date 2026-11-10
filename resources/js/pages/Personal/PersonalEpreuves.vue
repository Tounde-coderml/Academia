<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";

interface Epreuve {
    id: number;
    title: string;
    filiere: string;
    niveau: string;
    matiere: string;
    academic_year: string;
    semester: string;
    type: string;
    action?: "consultation" | "telechargement";
    occurred_at?: string;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    mode: "favorites" | "history";
    epreuves: Pagination<Epreuve>;
}>();

const isFavorites = props.mode === "favorites";
const title = isFavorites ? "Mes favoris" : "Mon historique";

function paginationLabel(label: string): string {
    return label
        .replace(/<[^>]*>/g, "")
        .replaceAll("&laquo;", "«")
        .replaceAll("&raquo;", "»");
}

function removeFavorite(epreuveId: number): void {
    router.delete(`/epreuves/${epreuveId}/favori`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="title" />

    <main class="min-h-svh bg-slate-50 text-slate-900">
        <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
            <header class="border-b border-slate-300 pb-6">
                <Link
                    href="/dashboard"
                    class="text-sm font-semibold text-indigo-800 underline underline-offset-4"
                >
                    Espace Academia
                </Link>
                <h1
                    class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"
                >
                    {{ title }}
                </h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                    {{
                        isFavorites
                            ? "Retrouve les épreuves publiées que tu as enregistrées."
                            : "Retrouve tes consultations et téléchargements récents."
                    }}
                </p>
                <nav
                    class="mt-5 flex flex-wrap gap-3"
                    aria-label="Espace personnel"
                >
                    <Link
                        href="/mes-favoris"
                        class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                        :class="
                            isFavorites
                                ? 'border-indigo-900 bg-indigo-950 text-white'
                                : 'border-slate-400 bg-white text-slate-800 hover:bg-indigo-50'
                        "
                        :aria-current="isFavorites ? 'page' : undefined"
                    >
                        Mes favoris
                    </Link>
                    <Link
                        href="/historique"
                        class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                        :class="
                            !isFavorites
                                ? 'border-indigo-900 bg-indigo-950 text-white'
                                : 'border-slate-400 bg-white text-slate-800 hover:bg-indigo-50'
                        "
                        :aria-current="!isFavorites ? 'page' : undefined"
                    >
                        Mon historique
                    </Link>
                </nav>
            </header>

            <section
                class="overflow-hidden rounded-3xl border border-slate-300 bg-white shadow-sm"
                :aria-label="title"
            >
                <div
                    v-if="epreuves.data.length === 0"
                    class="px-6 py-16 text-center sm:px-10"
                >
                    <h2 class="text-lg font-bold text-slate-950">
                        {{
                            isFavorites
                                ? "Aucun favori pour le moment"
                                : "Aucune activité pour le moment"
                        }}
                    </h2>
                    <p
                        class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600"
                    >
                        {{
                            isFavorites
                                ? "Enregistre une épreuve depuis le catalogue pour la retrouver ici."
                                : "Tes consultations de fiches et téléchargements apparaîtront ici."
                        }}
                    </p>
                    <Link
                        href="/epreuves"
                        class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-900 bg-indigo-950 px-5 text-sm font-bold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800"
                    >
                        Parcourir le catalogue
                    </Link>
                </div>

                <ul v-else class="divide-y divide-slate-300">
                    <li
                        v-for="epreuve in epreuves.data"
                        :key="`${epreuve.id}-${epreuve.occurred_at ?? 'favorite'}`"
                        class="flex flex-wrap items-center justify-between gap-4 p-5 sm:px-7"
                    >
                        <div class="min-w-0">
                            <Link
                                :href="`/epreuves/${epreuve.id}`"
                                class="text-base font-bold text-indigo-900 underline decoration-indigo-300 underline-offset-4 hover:text-indigo-700"
                            >
                                {{ epreuve.title }}
                            </Link>
                            <p class="mt-1 text-sm text-slate-700">
                                {{ epreuve.matiere }} · {{ epreuve.filiere }} ·
                                {{ epreuve.niveau }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ epreuve.academic_year }} ·
                                {{ epreuve.semester }} ·
                                {{ epreuve.type.replaceAll("_", " ") }}
                            </p>
                            <p
                                v-if="epreuve.occurred_at"
                                class="mt-1 text-sm text-slate-600"
                            >
                                {{
                                    epreuve.action === "consultation"
                                        ? "Consultée"
                                        : "Téléchargée"
                                }}
                                · {{ epreuve.occurred_at }}
                            </p>
                        </div>
                        <button
                            v-if="isFavorites"
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-400 bg-white px-4 text-sm font-semibold text-slate-800 hover:border-rose-500 hover:bg-rose-50 hover:text-rose-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700"
                            @click="removeFavorite(epreuve.id)"
                        >
                            Retirer des favoris
                        </button>
                    </li>
                </ul>

                <nav
                    v-if="epreuves.links.length > 3"
                    class="flex flex-wrap justify-center gap-2 border-t border-slate-300 p-5"
                    aria-label="Pagination"
                >
                    <Link
                        v-for="(link, index) in epreuves.links"
                        :key="index"
                        :href="link.url ?? '#'"
                        :aria-current="link.active ? 'page' : undefined"
                        :aria-disabled="!link.url"
                        class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg border px-3 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                        :class="
                            link.active
                                ? 'border-indigo-900 bg-indigo-950 text-white'
                                : link.url
                                  ? 'border-slate-400 bg-white text-slate-800 hover:bg-indigo-50'
                                  : 'cursor-not-allowed border-slate-300 bg-slate-100 text-slate-400'
                        "
                    >
                        {{ paginationLabel(link.label) }}
                    </Link>
                </nav>
            </section>
        </div>
    </main>
</template>
