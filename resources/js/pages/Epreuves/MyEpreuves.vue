<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";

interface Epreuve { id: number; title: string; filiere: string; niveau: string; matiere: string; academic_year: string; semester: string; type: string; status: string; submitted_at: string; rejection_reason: string | null }
interface Pagination<T> { data: T[]; links: { url: string | null; label: string; active: boolean }[] }

defineProps<{ epreuves: Pagination<Epreuve>; canReview: boolean }>();
const page = usePage<{ flash: { status?: string } }>();

function paginationLabel(label: string): string {
    return label.replace(/<[^>]*>/g, "").replaceAll("&laquo;", "«").replaceAll("&raquo;", "»");
}
</script>

<template>
    <Head title="Mes épreuves" />
    <main class="mx-auto max-w-5xl space-y-6 px-4 py-10">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div><h1 class="text-2xl font-semibold text-slate-900">Mes épreuves</h1><p class="mt-1 text-sm text-slate-600">Suivi de tes dépôts et de leur statut.</p></div>
            <Link href="/epreuves/deposer" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Déposer une épreuve</Link>
            <Link v-if="canReview" href="/admin/revue-epreuves" class="rounded-md border border-indigo-700 px-4 py-2 text-sm font-semibold text-indigo-700">Valider les dépôts</Link>
        </header>
        <p v-if="page.props.flash.status" class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ page.props.flash.status }}</p>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="p-3">Épreuve</th><th class="p-3">Matière</th><th class="p-3">Année / semestre</th><th class="p-3">Statut</th><th class="p-3">Motif</th></tr></thead>
                <tbody><tr v-for="epreuve in epreuves.data" :key="epreuve.id" class="border-t border-slate-100">
                    <td class="p-3"><strong>{{ epreuve.title }}</strong><div class="text-xs text-slate-500">{{ epreuve.filiere }} · {{ epreuve.niveau }}</div></td>
                    <td class="p-3">{{ epreuve.matiere }}</td><td class="p-3">{{ epreuve.academic_year }} · {{ epreuve.semester }}</td>
                    <td class="p-3">{{ epreuve.status === 'pending' ? 'En attente' : epreuve.status === 'published' ? 'Publiée' : 'Refusée' }}</td>
                    <td class="p-3">{{ epreuve.rejection_reason ?? '—' }}</td>
                </tr><tr v-if="epreuves.data.length === 0"><td colspan="5" class="p-8 text-center text-slate-500">Aucun dépôt pour le moment.</td></tr></tbody>
            </table>
        </div>
        <nav class="flex gap-2"><Link v-for="link in epreuves.links" :key="link.label" :href="link.url ?? '# '" class="rounded border px-3 py-1 text-sm" :class="link.active ? 'bg-indigo-700 text-white' : 'bg-white'">{{ paginationLabel(link.label) }}</Link></nav>
    </main>
</template>
