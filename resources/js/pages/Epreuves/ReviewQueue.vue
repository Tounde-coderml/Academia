<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { reactive, ref } from "vue";

interface Epreuve {
    id: number; title: string; author: string; filiere: string; niveau: string;
    matiere: string; academic_year: string; semester: string; type: string;
    submitted_at: string; has_duplicate: boolean;
}
interface Pagination<T> { data: T[]; links: { url: string | null; label: string; active: boolean }[] }

defineProps<{ epreuves: Pagination<Epreuve> }>();
const page = usePage<{ flash: { status?: string } }>();
const reasons = reactive<Record<number, string>>({});
const rejectionErrors = reactive<Record<number, string>>({});
const duplicateConfirmationId = ref<number | null>(null);

function paginationLabel(label: string): string {
    return label.replace(/<[^>]*>/g, "").replaceAll("&laquo;", "«").replaceAll("&raquo;", "»");
}

function publish(epreuve: Epreuve): void {
    const confirmed = duplicateConfirmationId.value === epreuve.id;
    router.post(`/admin/revue-epreuves/${epreuve.id}/publier`, { duplicate_confirmed: confirmed }, {
        preserveScroll: true,
        onError: (errors) => {
            if (errors.duplicate) duplicateConfirmationId.value = epreuve.id;
        },
    });
}

function reject(epreuve: Epreuve): void {
    router.post(`/admin/revue-epreuves/${epreuve.id}/refuser`, { reason: reasons[epreuve.id] ?? "" }, {
        preserveScroll: true,
        onError: (errors) => { rejectionErrors[epreuve.id] = String(errors.reason ?? ""); },
    });
}
</script>

<template>
    <Head title="Validation des épreuves" />
    <main class="mx-auto max-w-6xl space-y-6 px-4 py-10">
        <header>
            <Link href="/mes-epreuves" class="text-sm text-indigo-700">Mes épreuves</Link>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">Épreuves en attente</h1>
            <p class="mt-1 text-sm text-slate-600">Vérifie les informations et le fichier avant publication ou refus.</p>
        </header>
        <p v-if="page.props.flash.status" class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ page.props.flash.status }}</p>
        <section class="space-y-4">
            <article v-for="epreuve in epreuves.data" :key="epreuve.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ epreuve.title }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ epreuve.matiere }} · {{ epreuve.filiere }} · {{ epreuve.niveau }}</p>
                        <p class="text-sm text-slate-600">{{ epreuve.academic_year }} · {{ epreuve.semester }} · {{ epreuve.type }} · déposée par {{ epreuve.author }}</p>
                    </div>
                    <a :href="`/epreuves/${epreuve.id}/fichier`" class="text-sm font-medium text-indigo-700">Ouvrir le fichier</a>
                </div>
                <p v-if="epreuve.has_duplicate" class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900">Une épreuve identique existe déjà. La publication demande une confirmation explicite.</p>
                <p v-if="duplicateConfirmationId === epreuve.id" class="mt-3 text-sm font-medium text-amber-900">Confirme la publication du doublon en cliquant à nouveau sur le bouton.</p>
                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <button class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white" @click="publish(epreuve)">{{ duplicateConfirmationId === epreuve.id ? "Confirmer la publication" : "Publier" }}</button>
                    <div class="min-w-64 flex-1">
                        <label class="block text-sm font-medium">Motif obligatoire en cas de refus
                            <input v-model="reasons[epreuve.id]" class="mt-1 w-full rounded-md border-slate-300" maxlength="2000">
                        </label>
                        <p v-if="rejectionErrors[epreuve.id]" class="mt-1 text-sm text-red-700">{{ rejectionErrors[epreuve.id] }}</p>
                    </div>
                    <button class="rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-800" @click="reject(epreuve)">Refuser</button>
                </div>
            </article>
            <p v-if="epreuves.data.length === 0" class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">Aucune épreuve à valider.</p>
        </section>
        <nav class="flex gap-2"><Link v-for="link in epreuves.links" :key="link.label" :href="link.url ?? '# '" class="rounded border px-3 py-1 text-sm" :class="link.active ? 'bg-indigo-700 text-white' : 'bg-white'">{{ paginationLabel(link.label) }}</Link></nav>
    </main>
</template>
