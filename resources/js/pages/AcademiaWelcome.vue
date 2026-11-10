<script setup lang="ts">
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

type SchoolStatistics = {
    activeUsers: number;
    exams: {
        total: number;
        pending: number;
        published: number;
        rejected: number;
    };
    activity: {
        consultations: number;
        downloads: number;
    };
};

const props = defineProps<{
    title?: string;
    userName?: string | null;
    isAuthenticated?: boolean;
    canSubmitExams?: boolean;
    canReviewExams?: boolean;
    canManageExams?: boolean;
    canManageUsers?: boolean;
    canManageReferentials?: boolean;
    canViewSchoolStats?: boolean;
    statistics?: SchoolStatistics | null;
}>();

const logoutForm = useForm({});
const numberFormatter = new Intl.NumberFormat("fr-FR");
const statisticCards = computed(() => {
    if (!props.statistics) {
        return [];
    }

    return [
        { label: "Comptes actifs", value: props.statistics.activeUsers },
        { label: "Épreuves déposées", value: props.statistics.exams.total },
        {
            label: "Épreuves en attente",
            value: props.statistics.exams.pending,
        },
        { label: "Épreuves publiées", value: props.statistics.exams.published },
        { label: "Épreuves refusées", value: props.statistics.exams.rejected },
        {
            label: "Consultations enregistrées",
            value: props.statistics.activity.consultations,
        },
        {
            label: "Téléchargements enregistrés",
            value: props.statistics.activity.downloads,
        },
    ];
});

function logout(): void {
    logoutForm.post("/logout");
}
</script>

<template>
    <Head :title="title ?? 'Bienvenue sur Academia'" />

    <main class="min-h-svh bg-slate-50 text-slate-900">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
            <header
                class="mb-8 flex flex-wrap items-center justify-between gap-4 border-b border-slate-300 pb-5"
            >
                <Link
                    href="/dashboard"
                    class="group inline-flex items-center gap-3"
                >
                    <span
                        class="grid size-12 place-items-center rounded-2xl bg-indigo-950 text-sm font-black tracking-wide text-white shadow-sm"
                        aria-hidden="true"
                    >
                        A
                    </span>
                    <span>
                        <span
                            class="block text-lg font-bold tracking-tight text-slate-950"
                        >
                            Academia
                        </span>
                        <span class="block text-sm text-slate-600">
                            ESGC VAK · Espace académique
                        </span>
                    </span>
                </Link>

                <form v-if="isAuthenticated" @submit.prevent="logout">
                    <button
                        type="submit"
                        :disabled="logoutForm.processing"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-400 bg-white px-5 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-500 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700 disabled:cursor-wait disabled:opacity-60"
                    >
                        {{
                            logoutForm.processing
                                ? "Déconnexion…"
                                : "Se déconnecter"
                        }}
                    </button>
                </form>
            </header>

            <section
                class="overflow-hidden rounded-3xl border border-slate-300 bg-white shadow-sm"
            >
                <div
                    class="grid lg:grid-cols-[minmax(0,1.1fr)_minmax(23rem,0.9fr)]"
                >
                    <div
                        class="relative isolate overflow-hidden bg-gradient-to-br from-indigo-950 via-indigo-900 to-slate-900 px-6 py-10 text-white sm:px-10 sm:py-14 lg:px-12 lg:py-16"
                    >
                        <div
                            class="pointer-events-none absolute -right-20 -top-24 -z-10 size-80 rounded-full border border-white/10"
                            aria-hidden="true"
                        />
                        <div
                            class="pointer-events-none absolute -right-8 -top-12 -z-10 size-56 rounded-full border border-white/10"
                            aria-hidden="true"
                        />
                        <p
                            class="inline-flex items-center rounded-full border border-white/25 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-indigo-100"
                        >
                            Bibliothèque des épreuves
                        </p>
                        <h1
                            class="mt-6 max-w-2xl text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-5xl"
                        >
                            {{ title ?? "Bienvenue sur Academia" }}
                        </h1>
                        <p
                            class="mt-5 max-w-2xl text-base leading-7 text-indigo-100 sm:text-lg"
                        >
                            Dépose tes épreuves, suis leur validation et
                            retrouve les ressources de ton parcours au même
                            endroit.
                        </p>
                        <p
                            v-if="userName"
                            class="mt-8 inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-medium text-white"
                        >
                            <span
                                class="grid size-8 place-items-center rounded-full bg-white/15 text-xs font-bold"
                                aria-hidden="true"
                            >
                                {{ userName.slice(0, 1).toUpperCase() }}
                            </span>
                            Connecté en tant que {{ userName }}
                        </p>
                    </div>

                    <div class="p-6 sm:p-9 lg:p-10">
                        <div class="mb-6 border-b border-slate-300 pb-5">
                            <p
                                class="text-sm font-semibold uppercase tracking-[0.14em] text-indigo-800"
                            >
                                Accès rapide
                            </p>
                            <h2
                                class="mt-2 text-2xl font-bold tracking-tight text-slate-950"
                            >
                                Ton espace de travail
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Choisis une action pour continuer.
                            </p>
                        </div>

                        <nav
                            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2"
                            aria-label="Actions de l’espace Academia"
                        >
                            <Link
                                v-if="isAuthenticated"
                                href="/epreuves"
                                class="group flex min-h-28 items-start justify-between gap-4 rounded-2xl border border-indigo-300 bg-indigo-50/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-600 hover:bg-indigo-100 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                            >
                                <span>
                                    <span
                                        class="block text-base font-bold text-indigo-950"
                                        >Consulter le catalogue</span
                                    >
                                    <span
                                        class="mt-1.5 block text-sm leading-5 text-indigo-900/75"
                                        >Rechercher les épreuves publiées.</span
                                    >
                                </span>
                                <span
                                    class="text-lg font-semibold text-indigo-800"
                                    aria-hidden="true"
                                    >→</span
                                >
                            </Link>

                            <Link
                                v-if="isAuthenticated"
                                href="/mes-epreuves"
                                class="group flex min-h-28 items-start justify-between gap-4 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-500 hover:bg-indigo-50/50 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                            >
                                <span>
                                    <span
                                        class="block text-base font-bold text-slate-950"
                                        >Mes épreuves</span
                                    >
                                    <span
                                        class="mt-1.5 block text-sm leading-5 text-slate-600"
                                        >Consulter mes dépôts et leur
                                        statut.</span
                                    >
                                </span>
                                <span
                                    class="text-lg font-semibold text-indigo-800"
                                    aria-hidden="true"
                                    >→</span
                                >
                            </Link>

                            <Link
                                v-if="isAuthenticated"
                                href="/mes-favoris"
                                class="group flex min-h-28 items-start justify-between gap-4 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-500 hover:bg-indigo-50/50 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                            >
                                <span>
                                    <span
                                        class="block text-base font-bold text-slate-950"
                                        >Mes favoris et mon historique</span
                                    >
                                    <span
                                        class="mt-1.5 block text-sm leading-5 text-slate-600"
                                        >Retrouver les épreuves enregistrées et
                                        consultées.</span
                                    >
                                </span>
                                <span
                                    class="text-lg font-semibold text-indigo-800"
                                    aria-hidden="true"
                                    >→</span
                                >
                            </Link>

                            <Link
                                v-if="canSubmitExams"
                                href="/epreuves/deposer"
                                class="group flex min-h-28 items-start justify-between gap-4 rounded-2xl border border-indigo-300 bg-indigo-50/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-600 hover:bg-indigo-100 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                            >
                                <span>
                                    <span
                                        class="block text-base font-bold text-indigo-950"
                                        >Déposer une épreuve</span
                                    >
                                    <span
                                        class="mt-1.5 block text-sm leading-5 text-indigo-900/75"
                                        >Ajouter un document à la
                                        bibliothèque.</span
                                    >
                                </span>
                                <span
                                    class="text-lg font-semibold text-indigo-800"
                                    aria-hidden="true"
                                    >→</span
                                >
                            </Link>

                            <Link
                                v-if="canReviewExams"
                                href="/admin/revue-epreuves"
                                class="group flex min-h-28 items-start justify-between gap-4 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-500 hover:bg-indigo-50/50 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                            >
                                <span>
                                    <span
                                        class="block text-base font-bold text-slate-950"
                                        >Valider les dépôts</span
                                    >
                                    <span
                                        class="mt-1.5 block text-sm leading-5 text-slate-600"
                                        >Examiner les épreuves en attente.</span
                                    >
                                </span>
                                <span
                                    class="text-lg font-semibold text-indigo-800"
                                    aria-hidden="true"
                                    >→</span
                                >
                            </Link>

                            <Link
                                v-if="canManageExams"
                                href="/admin/epreuves"
                                class="group flex min-h-28 items-start justify-between gap-4 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-500 hover:bg-indigo-50/50 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                            >
                                <span>
                                    <span
                                        class="block text-base font-bold text-slate-950"
                                        >Gérer les épreuves</span
                                    >
                                    <span
                                        class="mt-1.5 block text-sm leading-5 text-slate-600"
                                        >Modifier ou supprimer un dépôt.</span
                                    >
                                </span>
                                <span
                                    class="text-lg font-semibold text-indigo-800"
                                    aria-hidden="true"
                                    >→</span
                                >
                            </Link>

                            <Link
                                v-if="canManageUsers"
                                href="/admin/utilisateurs"
                                class="group flex min-h-28 items-start justify-between gap-4 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-500 hover:bg-indigo-50/50 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                            >
                                <span>
                                    <span
                                        class="block text-base font-bold text-slate-950"
                                        >Gérer les comptes</span
                                    >
                                    <span
                                        class="mt-1.5 block text-sm leading-5 text-slate-600"
                                        >Administrer les utilisateurs de la
                                        plateforme.</span
                                    >
                                </span>
                                <span
                                    class="text-lg font-semibold text-indigo-800"
                                    aria-hidden="true"
                                    >→</span
                                >
                            </Link>

                            <Link
                                v-if="canManageReferentials"
                                href="/admin/filieres"
                                class="group flex min-h-28 items-start justify-between gap-4 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-500 hover:bg-indigo-50/50 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                            >
                                <span>
                                    <span
                                        class="block text-base font-bold text-slate-950"
                                        >Gérer les référentiels</span
                                    >
                                    <span
                                        class="mt-1.5 block text-sm leading-5 text-slate-600"
                                        >Mettre à jour le catalogue
                                        pédagogique.</span
                                    >
                                </span>
                                <span
                                    class="text-lg font-semibold text-indigo-800"
                                    aria-hidden="true"
                                    >→</span
                                >
                            </Link>
                        </nav>
                    </div>
                </div>
            </section>

            <section
                v-if="canViewSchoolStats && statistics"
                id="school-statistics"
                class="mt-6 rounded-3xl border border-slate-300 bg-white p-6 shadow-sm sm:p-8"
                aria-labelledby="school-statistics-title"
            >
                <div class="mb-6">
                    <p
                        class="text-sm font-semibold uppercase tracking-[0.14em] text-indigo-800"
                    >
                        Synthèse de la plateforme
                    </p>
                    <h2
                        id="school-statistics-title"
                        class="mt-2 text-2xl font-bold tracking-tight text-slate-950"
                    >
                        Indicateurs globaux
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Totaux enregistrés pour l’ensemble de la plateforme.
                    </p>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="card in statisticCards"
                        :key="card.label"
                        class="rounded-2xl border border-slate-300 bg-slate-50 p-5"
                    >
                        <dt class="text-sm font-medium text-slate-600">
                            {{ card.label }}
                        </dt>
                        <dd class="mt-2 text-3xl font-bold text-indigo-950">
                            {{ numberFormatter.format(card.value) }}
                        </dd>
                    </div>
                </dl>
            </section>

            <footer
                class="mt-6 flex flex-wrap items-center justify-between gap-3 px-1 text-xs text-slate-500"
            >
                <span>Academia · ESGC VAK</span>
                <span>Bibliothèque des épreuves</span>
            </footer>
        </div>
    </main>
</template>
