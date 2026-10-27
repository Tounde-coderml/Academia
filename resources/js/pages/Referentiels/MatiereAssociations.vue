<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";

interface Choice {
    id: number;
    code: string;
    name: string;
    is_active?: boolean;
}

interface Association {
    id: number;
    filiere: Choice;
    niveau: Choice;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    matiere: Choice;
    associations: Pagination<Association>;
    filieres: Choice[];
    niveaux: (Choice & { sort_order: number })[];
}>();

const form = useForm({ filiere_id: "", niveau_id: "" });
const page = usePage<{ flash: { status?: string } }>();
const errorSummary = ref<HTMLElement | null>(null);
const networkError = ref(false);
const retryAction = ref<(() => void) | null>(null);
const baseUrl = `/admin/matieres/${props.matiere.id}/associations`;

function submit(): void {
    request(() => {
        form.post(baseUrl, {
            onError: () => nextTick(() => errorSummary.value?.focus()),
            onNetworkError: () => (networkError.value = true),
        });
    });
}

function goToPage(url: string | null): void {
    if (url) {
        request(() => {
            router.visit(url, {
                preserveScroll: true,
                onNetworkError: () => (networkError.value = true),
            });
        });
    }
}

function removeAssociation(association: Association): void {
    if (!window.confirm("Supprimer cette association ?")) {
        return;
    }

    request(() => {
        router.delete(`${baseUrl}/${association.id}`, {
            preserveScroll: true,
            onStart: () => (form.processing = true),
            onFinish: () => (form.processing = false),
            onNetworkError: () => (networkError.value = true),
        });
    });
}

function request(action: () => void): void {
    networkError.value = false;
    retryAction.value = action;
    action();
}

function retryLastRequest(): void {
    if (retryAction.value) {
        retryAction.value();
    }
}
</script>

<template>
    <Head :title="`Associations — ${matiere.name}`" />

    <main class="mx-auto flex min-h-svh w-full max-w-5xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <header>
            <Link href="/admin/matieres" class="inline-flex min-h-11 items-center rounded text-sm font-semibold text-indigo-800 underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-700">
                ← Retour aux matières
            </Link>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950">Associations de {{ matiere.name }}</h1>
            <p class="mt-2 text-sm text-slate-700">Code {{ matiere.code }}. Seules les valeurs actives peuvent être associées.</p>
        </header>

        <p v-if="page.props.flash.status" role="status" class="rounded-lg bg-emerald-50 p-4 text-sm font-medium text-emerald-950">{{ page.props.flash.status }}</p>
        <div v-if="networkError" role="alert" class="flex flex-col gap-3 rounded-lg border border-red-700 bg-red-50 p-4 text-sm text-red-900 sm:flex-row sm:items-center sm:justify-between">
            <p>L’opération n’a pas abouti à cause d’un problème réseau. Les valeurs saisies sont conservées.</p>
            <button type="button" :disabled="form.processing" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-red-800 px-4 font-semibold hover:bg-red-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800 disabled:opacity-60" @click="retryLastRequest">
                Réessayer
            </button>
        </div>

        <div v-if="Object.keys(form.errors).length" ref="errorSummary" tabindex="-1" role="alert" class="rounded-lg border border-red-700 bg-red-50 p-4 text-sm text-red-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800">
            <ul class="list-inside list-disc">
                <li v-for="[field, message] in Object.entries(form.errors)" :key="field">{{ message }}</li>
            </ul>
        </div>

        <form class="grid gap-4 rounded-xl border border-slate-300 bg-white p-5 sm:grid-cols-3 sm:items-end" @submit.prevent="submit">
            <div class="space-y-2">
                <label for="filiere_id" class="block text-sm font-medium text-slate-900">Filière</label>
                <select id="filiere_id" v-model="form.filiere_id" required class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">
                    <option value="" disabled>Choisir une filière</option>
                    <option v-for="filiere in filieres" :key="filiere.id" :value="String(filiere.id)">{{ filiere.code }} — {{ filiere.name }}</option>
                </select>
            </div>
            <div class="space-y-2">
                <label for="niveau_id" class="block text-sm font-medium text-slate-900">Niveau global</label>
                <select id="niveau_id" v-model="form.niveau_id" required class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">
                    <option value="" disabled>Choisir un niveau</option>
                    <option v-for="niveau in niveaux" :key="niveau.id" :value="String(niveau.id)">{{ niveau.code }} — {{ niveau.name }}</option>
                </select>
            </div>
            <button type="submit" :disabled="form.processing || !matiere.is_active || !filieres.length || !niveaux.length" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-indigo-800 px-5 text-sm font-semibold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:cursor-not-allowed disabled:opacity-60">
                Ajouter l’association
            </button>
            <p v-if="!matiere.is_active" class="text-sm text-amber-900 sm:col-span-3">Cette matière est inactive : aucune nouvelle association ne peut être ajoutée.</p>
            <p v-else-if="!filieres.length || !niveaux.length" class="text-sm text-amber-900 sm:col-span-3">Activez au moins une filière et un niveau pour créer une association.</p>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-300 bg-white" aria-label="Associations existantes">
            <div v-if="associations.data.length" class="overflow-x-auto">
                <table class="w-full min-w-[36rem] border-collapse text-left text-sm">
                    <thead class="bg-slate-100 text-slate-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Filière</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Niveau global</th>
                            <th scope="col" class="px-4 py-3 font-semibold">État</th>
                            <th scope="col" class="px-4 py-3 font-semibold"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="association in associations.data" :key="association.id">
                            <td class="px-4 py-4">{{ association.filiere.code }} — {{ association.filiere.name }}</td>
                            <td class="px-4 py-4">{{ association.niveau.code }} — {{ association.niveau.name }}</td>
                            <td class="px-4 py-4">
                                <span v-if="association.filiere.is_active && association.niveau.is_active && matiere.is_active" class="font-semibold text-emerald-900">Disponible</span>
                                <span v-else class="font-semibold text-slate-700">Inactif dans les choix</span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <button type="button" :disabled="form.processing" class="inline-flex min-h-10 items-center rounded px-3 text-sm font-semibold text-red-800 underline underline-offset-2 hover:text-red-950 focus-visible:outline-2 focus-visible:outline-red-800 disabled:opacity-60" @click="removeAssociation(association)">
                                    Supprimer
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="p-8 text-center text-sm text-slate-700">Aucune association pour cette matière.</p>
        </section>

        <nav v-if="associations.links.length > 3" aria-label="Pagination des associations" class="flex flex-wrap justify-center gap-2">
            <button v-for="link in associations.links" :key="link.label" type="button" :disabled="!link.url || form.processing" :aria-current="link.active ? 'page' : undefined" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border px-3 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700 disabled:cursor-not-allowed disabled:opacity-50" :class="link.active ? 'border-indigo-800 bg-indigo-800 text-white' : 'border-slate-400 text-slate-800 hover:bg-slate-100'" @click="goToPage(link.url)">{{ link.label }}</button>
        </nav>
    </main>
</template>
