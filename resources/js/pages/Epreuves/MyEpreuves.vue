<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";

interface Epreuve {
    id: number;
    title: string;
    filiere: string;
    niveau: string;
    matiere: string;
    academic_year: string;
    semester: string;
    type: string;
    status: string;
    submitted_at: string;
    rejection_reason: string | null;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{ epreuves: Pagination<Epreuve>; canReview: boolean }>();
const page = usePage<{ flash: { status?: string } }>();

function paginationLabel(label: string): string {
    return label
        .replace(/<[^>]*>/g, "")
        .replaceAll("&laquo;", "«")
        .replaceAll("&raquo;", "»");
}

function statusLabel(status: string): string {
    if (status === "pending") return "En attente";
    if (status === "published") return "Publiée";

    return "Refusée";
}

function statusClass(status: string): string {
    if (status === "pending")
        return "border-amber-300 bg-amber-50 text-amber-900";
    if (status === "published")
        return "border-emerald-300 bg-emerald-50 text-emerald-900";

    return "border-rose-300 bg-rose-50 text-rose-900";
}
</script>

<template>
    <Head title="Mes épreuves" />

    <main class="min-h-svh bg-slate-50 text-slate-900">
        <div
            class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12"
        >
            <header
                class="flex flex-col gap-6 border-b border-slate-300 pb-7 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <Link
                        href="/dashboard"
                        class="text-sm font-semibold text-indigo-800 underline decoration-indigo-300 underline-offset-4 hover:text-indigo-950"
                    >
                        Espace Academia
                    </Link>
                    <h1
                        class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"
                    >
                        Mes épreuves
                    </h1>
                    <p
                        class="mt-3 max-w-2xl text-base leading-7 text-slate-600"
                    >
                        Consulte tes dépôts, vérifie leur classement et suis
                        leur statut de validation.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Link
                        href="/epreuves/deposer"
                        class="inline-flex min-h-12 items-center justify-center rounded-xl border border-indigo-900 bg-indigo-950 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800"
                    >
                        Déposer une épreuve
                    </Link>
                    <Link
                        v-if="canReview"
                        href="/admin/revue-epreuves"
                        class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-400 bg-white px-5 text-sm font-bold text-slate-800 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800"
                    >
                        Valider les dépôts
                    </Link>
                </div>
            </header>

            <p
                v-if="page.props.flash.status"
                role="status"
                class="rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-950 shadow-sm"
            >
                {{ page.props.flash.status }}
            </p>

            <section
                class="overflow-hidden rounded-3xl border border-slate-300 bg-white shadow-sm"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-300 bg-slate-100/80 px-5 py-5 sm:px-7"
                >
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">
                            Historique de mes dépôts
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Les informations et retours liés à chaque épreuve.
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
                    <div
                        class="mx-auto grid size-14 place-items-center rounded-2xl border border-indigo-200 bg-indigo-50 text-2xl text-indigo-800"
                        aria-hidden="true"
                    >
                        ↑
                    </div>
                    <h3 class="mt-5 text-lg font-bold text-slate-950">
                        Aucun dépôt pour le moment
                    </h3>
                    <p
                        class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600"
                    >
                        Dépose une épreuve pour commencer à suivre son
                        traitement depuis cet espace.
                    </p>
                    <Link
                        href="/epreuves/deposer"
                        class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-900 bg-indigo-950 px-5 text-sm font-bold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800"
                    >
                        Déposer une épreuve
                    </Link>
                </div>

                <template v-if="epreuves.data.length > 0">
                    <div class="divide-y divide-slate-300 md:hidden">
                        <article
                            v-for="epreuve in epreuves.data"
                            :key="epreuve.id"
                            class="space-y-5 p-5 sm:p-7"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <h3
                                        class="break-words text-base font-bold leading-6 text-slate-950"
                                    >
                                        {{ epreuve.title }}
                                    </h3>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ epreuve.matiere }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex shrink-0 items-center rounded-full border px-3 py-1 text-xs font-bold"
                                    :class="statusClass(epreuve.status)"
                                >
                                    {{ statusLabel(epreuve.status) }}
                                </span>
                            </div>

                            <dl
                                class="grid grid-cols-2 gap-x-4 gap-y-4 rounded-2xl border border-slate-300 bg-slate-50 p-4 text-sm"
                            >
                                <div>
                                    <dt
                                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Filière · niveau
                                    </dt>
                                    <dd
                                        class="mt-1 font-semibold text-slate-900"
                                    >
                                        {{ epreuve.filiere }} ·
                                        {{ epreuve.niveau }}
                                    </dd>
                                </div>
                                <div>
                                    <dt
                                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Année · semestre
                                    </dt>
                                    <dd
                                        class="mt-1 font-semibold text-slate-900"
                                    >
                                        {{ epreuve.academic_year }} ·
                                        {{ epreuve.semester }}
                                    </dd>
                                </div>
                                <div class="col-span-2">
                                    <dt
                                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    >
                                        Type
                                    </dt>
                                    <dd
                                        class="mt-1 font-semibold capitalize text-slate-900"
                                    >
                                        {{ epreuve.type.replaceAll("_", " ") }}
                                    </dd>
                                </div>
                            </dl>

                            <div
                                class="rounded-2xl border border-slate-300 px-4 py-3"
                            >
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                                >
                                    Retour
                                </p>
                                <p
                                    class="mt-1 text-sm leading-6 text-slate-800"
                                >
                                    {{
                                        epreuve.rejection_reason ??
                                        "Aucun commentaire"
                                    }}
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-[980px] w-full text-left text-sm">
                            <thead
                                class="bg-slate-100 text-xs font-bold uppercase tracking-wide text-slate-700"
                            >
                                <tr class="border-b border-slate-300">
                                    <th scope="col" class="px-6 py-4">
                                        Épreuve
                                    </th>
                                    <th scope="col" class="px-6 py-4">
                                        Matière
                                    </th>
                                    <th scope="col" class="px-6 py-4">
                                        Année · semestre
                                    </th>
                                    <th scope="col" class="px-6 py-4">
                                        Statut
                                    </th>
                                    <th scope="col" class="px-6 py-4">
                                        Retour
                                    </th>
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
                                            {{ epreuve.filiere }} ·
                                            {{ epreuve.niveau }}
                                        </p>
                                    </td>
                                    <td
                                        class="px-6 py-5 font-medium text-slate-800"
                                    >
                                        {{ epreuve.matiere }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-5 text-slate-700"
                                    >
                                        {{ epreuve.academic_year }} ·
                                        {{ epreuve.semester }}
                                    </td>
                                    <td class="px-6 py-5">
                                        <span
                                            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold"
                                            :class="statusClass(epreuve.status)"
                                        >
                                            {{ statusLabel(epreuve.status) }}
                                        </span>
                                    </td>
                                    <td
                                        class="max-w-sm whitespace-normal px-6 py-5 leading-6 text-slate-700"
                                    >
                                        {{ epreuve.rejection_reason ?? "—" }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
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
