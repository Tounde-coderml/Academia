<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";

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
    status: string;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{ epreuves: Pagination<Epreuve> }>();
const page = usePage<{ flash: { status?: string } }>();

function paginationLabel(label: string): string {
    return label
        .replace(/<[^>]*>/g, "")
        .replaceAll("&laquo;", "«")
        .replaceAll("&raquo;", "»");
}

function destroy(epreuve: Epreuve): void {
    if (
        !window.confirm(
            `Supprimer définitivement « ${epreuve.title} » et son fichier ?`,
        )
    )
        return;
    router.delete(`/admin/epreuves/${epreuve.id}`);
}
</script>

<template>
    <Head title="Gestion des épreuves" />

    <main class="min-h-svh bg-slate-50 text-slate-900">
        <div
            class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12"
        >
            <header class="border-b border-slate-300 pb-7">
                <Link
                    href="/dashboard"
                    class="text-sm font-semibold text-indigo-800 underline decoration-indigo-300 underline-offset-4 hover:text-indigo-950"
                >
                    Espace Academia
                </Link>
                <h1
                    class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"
                >
                    Gestion des épreuves
                </h1>
                <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                    Consulte, corrige ou retire les épreuves déposées dans la
                    bibliothèque.
                </p>
            </header>

            <p
                v-if="page.props.flash.status"
                role="status"
                class="rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-950 shadow-sm"
            >
                {{ page.props.flash.status }}
            </p>

            <section
                class="overflow-hidden rounded-3xl border-2 border-slate-300 bg-white shadow-sm"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-300 bg-slate-100/80 px-5 py-5 sm:px-7"
                >
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">
                            Tous les dépôts
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Les actions d’administration sont réservées aux
                            comptes autorisés.
                        </p>
                    </div>
                    <span
                        class="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700"
                    >
                        {{ epreuves.data.length }} sur cette page
                    </span>
                </div>

                <div
                    v-if="epreuves.data.length === 0"
                    class="px-6 py-16 text-center sm:px-10"
                >
                    <h3 class="text-lg font-bold text-slate-950">
                        Aucune épreuve enregistrée
                    </h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Les dépôts apparaîtront ici lorsqu’ils seront soumis.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-[1000px] w-full text-left text-sm">
                        <thead
                            class="bg-slate-100 text-xs font-bold uppercase tracking-wide text-slate-700"
                        >
                            <tr class="border-b border-slate-300">
                                <th scope="col" class="px-6 py-4">Épreuve</th>
                                <th scope="col" class="px-6 py-4">
                                    Classement
                                </th>
                                <th scope="col" class="px-6 py-4">
                                    Année · semestre
                                </th>
                                <th scope="col" class="px-6 py-4">Type</th>
                                <th scope="col" class="px-6 py-4">Statut</th>
                                <th scope="col" class="px-6 py-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr
                                v-for="epreuve in epreuves.data"
                                :key="epreuve.id"
                                class="align-top transition hover:bg-indigo-50/40"
                            >
                                <td class="max-w-xs px-6 py-5">
                                    <p
                                        class="break-words font-bold text-slate-950"
                                    >
                                        {{ epreuve.title }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        Déposée par {{ epreuve.author }}
                                    </p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="font-semibold text-slate-900">
                                        {{ epreuve.matiere }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ epreuve.filiere }} ·
                                        {{ epreuve.niveau }}
                                    </p>
                                </td>
                                <td
                                    class="whitespace-nowrap px-6 py-5 text-slate-700"
                                >
                                    {{ epreuve.academic_year }} ·
                                    {{ epreuve.semester }}
                                </td>
                                <td class="px-6 py-5 capitalize text-slate-700">
                                    {{ epreuve.type.replaceAll("_", " ") }}
                                </td>
                                <td class="px-6 py-5">
                                    <span
                                        class="inline-flex rounded-full border px-3 py-1 text-xs font-bold"
                                        :class="
                                            epreuve.status === 'published'
                                                ? 'border-emerald-300 bg-emerald-50 text-emerald-900'
                                                : epreuve.status === 'pending'
                                                  ? 'border-amber-300 bg-amber-50 text-amber-900'
                                                  : 'border-rose-300 bg-rose-50 text-rose-900'
                                        "
                                    >
                                        {{
                                            epreuve.status === "published"
                                                ? "Publiée"
                                                : epreuve.status === "pending"
                                                  ? "En attente"
                                                  : "Refusée"
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <Link
                                            :href="`/admin/epreuves/${epreuve.id}/modifier`"
                                            class="inline-flex min-h-10 items-center justify-center rounded-lg border border-indigo-300 bg-white px-3 text-sm font-bold text-indigo-900 hover:border-indigo-600 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                                        >
                                            Modifier
                                        </Link>
                                        <button
                                            class="inline-flex min-h-10 items-center justify-center rounded-lg border border-rose-300 bg-white px-3 text-sm font-bold text-rose-800 hover:border-rose-500 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700"
                                            @click="destroy(epreuve)"
                                        >
                                            Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
