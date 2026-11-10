<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { reactive, ref } from "vue";

interface Epreuve {
    id: number;
    title: string;
    author: string;
    filiere: string;
    niveau: string;
    matiere: string;
    academic_year: string;
    semester: string;
    type: string;
    submitted_at: string;
    has_duplicate: boolean;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{ epreuves: Pagination<Epreuve> }>();
const page = usePage<{ flash: { status?: string } }>();
const reasons = reactive<Record<number, string>>({});
const rejectionErrors = reactive<Record<number, string>>({});
const duplicateConfirmationId = ref<number | null>(null);

function paginationLabel(label: string): string {
    return label
        .replace(/<[^>]*>/g, "")
        .replaceAll("&laquo;", "«")
        .replaceAll("&raquo;", "»");
}

function publish(epreuve: Epreuve): void {
    const confirmed = duplicateConfirmationId.value === epreuve.id;
    router.post(
        `/admin/revue-epreuves/${epreuve.id}/publier`,
        { duplicate_confirmed: confirmed },
        {
            preserveScroll: true,
            onError: (errors) => {
                if (errors.duplicate)
                    duplicateConfirmationId.value = epreuve.id;
            },
        },
    );
}

function reject(epreuve: Epreuve): void {
    router.post(
        `/admin/revue-epreuves/${epreuve.id}/refuser`,
        { reason: reasons[epreuve.id] ?? "" },
        {
            preserveScroll: true,
            onError: (errors) => {
                rejectionErrors[epreuve.id] = String(errors.reason ?? "");
            },
        },
    );
}
</script>

<template>
    <Head title="Validation des épreuves" />

    <main class="min-h-svh bg-slate-50 text-slate-900">
        <div
            class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12"
        >
            <header class="border-b border-slate-300 pb-7">
                <Link
                    href="/mes-epreuves"
                    class="text-sm font-semibold text-indigo-800 underline decoration-indigo-300 underline-offset-4 hover:text-indigo-950"
                >
                    Mes épreuves
                </Link>
                <h1
                    class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"
                >
                    Épreuves en attente
                </h1>
                <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                    Vérifie le classement et le document de chaque dépôt avant
                    de le publier ou de le refuser.
                </p>
            </header>

            <p
                v-if="page.props.flash.status"
                role="status"
                class="rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-950 shadow-sm"
            >
                {{ page.props.flash.status }}
            </p>

            <section class="space-y-5" aria-label="Épreuves à valider">
                <article
                    v-for="epreuve in epreuves.data"
                    :key="epreuve.id"
                    class="overflow-hidden rounded-3xl border-2 border-slate-300 bg-white shadow-sm transition hover:border-slate-400 hover:shadow-md"
                >
                    <div
                        class="flex flex-col gap-5 border-b border-slate-300 bg-slate-100/70 px-5 py-6 sm:flex-row sm:items-start sm:justify-between sm:px-7"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-800"
                            >
                                {{ epreuve.matiere }} · {{ epreuve.filiere }} ·
                                {{ epreuve.niveau }}
                            </p>
                            <h2
                                class="mt-2 break-words text-xl font-bold leading-snug text-slate-950 sm:text-2xl"
                            >
                                {{ epreuve.title }}
                            </h2>
                            <p class="mt-2 text-sm text-slate-600">
                                Déposée par
                                <span class="font-semibold text-slate-800">{{
                                    epreuve.author
                                }}</span>
                            </p>
                        </div>
                        <a
                            :href="`/epreuves/${epreuve.id}/fichier`"
                            class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-xl border-2 border-indigo-300 bg-white px-5 text-sm font-bold text-indigo-900 shadow-sm transition hover:border-indigo-600 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800"
                        >
                            Ouvrir le fichier <span aria-hidden="true">↗</span>
                        </a>
                    </div>

                    <div
                        class="grid gap-3 px-5 py-5 sm:grid-cols-2 lg:grid-cols-4 sm:px-7"
                    >
                        <div
                            class="rounded-2xl border border-slate-300 bg-white p-4"
                        >
                            <p
                                class="text-xs font-bold uppercase tracking-wide text-slate-500"
                            >
                                Année scolaire
                            </p>
                            <p class="mt-2 font-semibold text-slate-950">
                                {{ epreuve.academic_year }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-300 bg-white p-4"
                        >
                            <p
                                class="text-xs font-bold uppercase tracking-wide text-slate-500"
                            >
                                Semestre
                            </p>
                            <p class="mt-2 font-semibold text-slate-950">
                                {{ epreuve.semester }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-300 bg-white p-4"
                        >
                            <p
                                class="text-xs font-bold uppercase tracking-wide text-slate-500"
                            >
                                Type
                            </p>
                            <p
                                class="mt-2 font-semibold capitalize text-slate-950"
                            >
                                {{ epreuve.type.replaceAll("_", " ") }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-slate-300 bg-white p-4"
                        >
                            <p
                                class="text-xs font-bold uppercase tracking-wide text-slate-500"
                            >
                                Date de dépôt
                            </p>
                            <p class="mt-2 font-semibold text-slate-950">
                                {{ epreuve.submitted_at }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="epreuve.has_duplicate"
                        class="mx-5 mb-5 rounded-2xl border-2 border-amber-400 bg-amber-50 p-4 text-sm leading-6 text-amber-950 shadow-sm sm:mx-7"
                    >
                        <p class="font-bold">Doublon détecté</p>
                        <p class="mt-1">
                            Une épreuve identique existe déjà. Confirme
                            explicitement avant de publier ce dépôt.
                        </p>
                    </div>
                    <p
                        v-if="duplicateConfirmationId === epreuve.id"
                        class="mx-5 mb-5 rounded-xl border border-amber-400 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-950 sm:mx-7"
                    >
                        Clique à nouveau sur « Confirmer la publication » pour
                        valider ce doublon.
                    </p>

                    <div
                        class="grid gap-5 border-t border-slate-300 bg-slate-50 px-5 py-6 sm:px-7 lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:items-end"
                    >
                        <button
                            class="inline-flex min-h-12 items-center justify-center rounded-xl border border-emerald-800 bg-emerald-800 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                            @click="publish(epreuve)"
                        >
                            {{
                                duplicateConfirmationId === epreuve.id
                                    ? "Confirmer la publication"
                                    : "Publier l’épreuve"
                            }}
                        </button>

                        <div class="min-w-0">
                            <label
                                :for="`rejection-reason-${epreuve.id}`"
                                class="block text-sm font-bold text-slate-800"
                            >
                                Motif de refus
                                <span class="font-normal text-slate-600"
                                    >(obligatoire pour refuser)</span
                                >
                            </label>
                            <input
                                :id="`rejection-reason-${epreuve.id}`"
                                v-model="reasons[epreuve.id]"
                                class="mt-2 min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 text-base text-slate-950 shadow-sm transition placeholder:text-slate-500 focus:border-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                                maxlength="2000"
                                placeholder="Explique ce qui doit être corrigé"
                                :aria-invalid="
                                    Boolean(rejectionErrors[epreuve.id])
                                "
                            />
                            <p
                                v-if="rejectionErrors[epreuve.id]"
                                class="mt-2 text-sm font-semibold text-rose-800"
                                role="alert"
                            >
                                {{ rejectionErrors[epreuve.id] }}
                            </p>
                        </div>

                        <button
                            class="inline-flex min-h-12 items-center justify-center rounded-xl border-2 border-rose-300 bg-white px-6 text-sm font-bold text-rose-800 transition hover:border-rose-500 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700"
                            @click="reject(epreuve)"
                        >
                            Refuser
                        </button>
                    </div>
                </article>

                <div
                    v-if="epreuves.data.length === 0"
                    class="rounded-3xl border-2 border-dashed border-slate-400 bg-white px-6 py-16 text-center"
                >
                    <h2 class="text-lg font-bold text-slate-950">
                        Aucune épreuve en attente
                    </h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Les nouveaux dépôts à examiner apparaîtront ici.
                    </p>
                </div>
            </section>

            <nav
                v-if="epreuves.links.length > 3"
                class="flex flex-wrap items-center gap-2"
                aria-label="Pagination des épreuves"
            >
                <Link
                    v-for="link in epreuves.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :aria-current="link.active ? 'page' : undefined"
                    class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    :class="
                        link.active
                            ? 'border-indigo-900 bg-indigo-950 text-white'
                            : link.url
                              ? 'border-slate-400 bg-white text-slate-800 hover:border-indigo-500 hover:bg-indigo-50'
                              : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                    "
                >
                    {{ paginationLabel(link.label) }}
                </Link>
            </nav>
        </div>
    </main>
</template>
