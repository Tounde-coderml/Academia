<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";

interface Epreuve { id: number; title: string; author: string; filiere: string; niveau: string; matiere: string; academic_year: string; semester: string; type: string; status: string }
interface Pagination<T> { data: T[]; links: { url: string | null; label: string; active: boolean }[] }

defineProps<{ epreuves: Pagination<Epreuve> }>();
const page = usePage<{ flash: { status?: string } }>();

function paginationLabel(label: string): string {
    return label.replace(/<[^>]*>/g, "").replaceAll("&laquo;", "«").replaceAll("&raquo;", "»");
}

function destroy(epreuve: Epreuve): void {
    if (!window.confirm(`Supprimer définitivement « ${epreuve.title} » et son fichier ?`)) return;
    router.delete(`/admin/epreuves/${epreuve.id}`);
}
</script>

<template>
    <Head title="Gestion des épreuves" />
    <main class="mx-auto max-w-6xl space-y-6 px-4 py-10">
        <header><h1 class="text-2xl font-semibold text-slate-900">Gestion des épreuves</h1><p class="mt-1 text-sm text-slate-600">Modification et suppression réservées à l’administration.</p></header>
        <p v-if="page.props.flash.status" class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ page.props.flash.status }}</p>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="p-3">Épreuve</th><th class="p-3">Classement</th><th class="p-3">Année / semestre</th><th class="p-3">Statut</th><th class="p-3">Actions</th></tr></thead>
                <tbody>
                    <tr v-for="epreuve in epreuves.data" :key="epreuve.id" class="border-t border-slate-100">
                        <td class="p-3"><strong>{{ epreuve.title }}</strong><div class="text-xs text-slate-500">Par {{ epreuve.author }}</div></td>
                        <td class="p-3">{{ epreuve.matiere }} · {{ epreuve.filiere }} · {{ epreuve.niveau }}</td>
                        <td class="p-3">{{ epreuve.academic_year }} · {{ epreuve.semester }}</td><td class="p-3">{{ epreuve.status }}</td>
                        <td class="space-x-3 p-3"><Link :href="`/admin/epreuves/${epreuve.id}/modifier`" class="font-medium text-indigo-700">Modifier</Link><button class="font-medium text-red-700" @click="destroy(epreuve)">Supprimer</button></td>
                    </tr>
                    <tr v-if="epreuves.data.length === 0"><td colspan="5" class="p-8 text-center text-slate-500">Aucune épreuve enregistrée.</td></tr>
                </tbody>
            </table>
        </div>
        <nav class="flex gap-2"><Link v-for="link in epreuves.links" :key="link.label" :href="link.url ?? '# '" class="rounded border px-3 py-1 text-sm" :class="link.active ? 'bg-indigo-700 text-white' : 'bg-white'">{{ paginationLabel(link.label) }}</Link></nav>
    </main>
</template>
