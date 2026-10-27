<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { reactive, ref } from "vue";

type Resource = "filieres" | "niveaux" | "matieres";
type StatusFilter = "all" | "active" | "inactive";

interface ReferentialItem {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
    associations_count: number;
    sort_order?: number;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    title: string;
    resource: Resource;
    items: Pagination<ReferentialItem>;
    filters: { search: string; status: StatusFilter };
}>();

const page = usePage<{ flash: { status?: string } }>();
const filters = reactive({ ...props.filters });
const networkError = ref(false);
const processing = ref(false);
const retryAction = ref<(() => void) | null>(null);
const baseUrl = `/admin/${props.resource}`;
const newItemLabel =
    props.resource === "filieres"
        ? "une filière"
        : props.resource === "niveaux"
          ? "un niveau"
          : "une matière";
const resources: { key: Resource; label: string }[] = [
    { key: "filieres", label: "Filières" },
    { key: "niveaux", label: "Niveaux" },
    { key: "matieres", label: "Matières" },
];

function applyFilters(): void {
    request(() => {
        router.get(baseUrl, filters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onNetworkError: () => (networkError.value = true),
        });
    });
}

function deactivate(item: ReferentialItem): void {
    if (!window.confirm(`Désactiver ${item.name} ?`)) {
        return;
    }

    request(() => {
        router.post(`${baseUrl}/${item.id}/desactiver`, {}, {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onNetworkError: () => (networkError.value = true),
        });
    });
}

function reactivate(item: ReferentialItem): void {
    request(() => {
        router.post(`${baseUrl}/${item.id}/reactiver`, {}, {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onNetworkError: () => (networkError.value = true),
        });
    });
}

function remove(item: ReferentialItem): void {
    if (!window.confirm(`Supprimer ${item.name} si cette valeur n’est pas utilisée ?`)) {
        return;
    }

    request(() => {
        router.delete(`${baseUrl}/${item.id}`, {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
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

function visitPage(url: string | null): void {
    if (url) {
        request(() => {
            router.visit(url, {
                preserveScroll: true,
                onStart: () => (processing.value = true),
                onFinish: () => (processing.value = false),
                onNetworkError: () => (networkError.value = true),
            });
        });
    }
}
</script>

<template>
    <Head :title="title" />

    <main class="mx-auto flex min-h-svh w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="/" class="inline-flex min-h-11 items-center rounded text-sm font-semibold tracking-wide text-indigo-700 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-700">
                    ACADEMIA
                </a>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ title }}</h1>
                <p class="mt-2 text-sm text-slate-700">Gestion des référentiels académiques.</p>
            </div>
            <Link :href="`${baseUrl}/create`" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-800 px-5 text-sm font-semibold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800">
                Ajouter {{ newItemLabel }}
            </Link>
        </header>

        <nav aria-label="Référentiels" class="flex flex-wrap gap-2 border-b border-slate-300 pb-3">
            <Link
                v-for="entry in resources"
                :key="entry.key"
                :href="`/admin/${entry.key}`"
                :aria-current="resource === entry.key ? 'page' : undefined"
                class="inline-flex min-h-11 items-center rounded-lg px-4 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                :class="resource === entry.key ? 'bg-indigo-100 text-indigo-950' : 'text-slate-700 hover:bg-slate-100'"
            >
                {{ entry.label }}
            </Link>
        </nav>

        <p v-if="page.props.flash.status" role="status" class="rounded-lg bg-emerald-50 p-4 text-sm font-medium text-emerald-950">
            {{ page.props.flash.status }}
        </p>
        <div v-if="networkError" role="alert" class="flex flex-col gap-3 rounded-lg border border-red-700 bg-red-50 p-4 text-sm text-red-900 sm:flex-row sm:items-center sm:justify-between">
            <p>La requête n’a pas abouti à cause d’un problème réseau. Vos filtres sont conservés.</p>
            <button type="button" :disabled="processing" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-red-800 px-4 font-semibold hover:bg-red-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800 disabled:opacity-60" @click="retryLastRequest">
                Réessayer
            </button>
        </div>

        <form class="grid gap-4 rounded-xl border border-slate-300 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end" @submit.prevent="applyFilters">
            <div class="space-y-2">
                <label for="search" class="block text-sm font-medium text-slate-900">Rechercher par code ou intitulé</label>
                <input id="search" v-model="filters.search" type="search" maxlength="100" :disabled="processing" class="min-h-11 w-full rounded-lg border border-slate-500 px-3 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700 disabled:opacity-60" />
            </div>
            <div class="space-y-2">
                <label for="status" class="block text-sm font-medium text-slate-900">État</label>
                <select id="status" v-model="filters.status" :disabled="processing" class="min-h-11 w-full rounded-lg border border-slate-500 bg-white px-3 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700 disabled:opacity-60">
                    <option value="all">Tous</option>
                    <option value="active">Actifs</option>
                    <option value="inactive">Inactifs</option>
                </select>
            </div>
            <button type="submit" :disabled="processing" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-indigo-800 px-5 text-sm font-semibold text-indigo-900 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:opacity-60">
                {{ processing ? "Chargement…" : "Filtrer" }}
            </button>
        </form>

        <section aria-label="Liste des référentiels" class="overflow-hidden rounded-xl border border-slate-300 bg-white">
            <div v-if="items.data.length" class="overflow-x-auto">
                <table class="w-full min-w-[42rem] border-collapse text-left text-sm">
                    <thead class="bg-slate-100 text-slate-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Code</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Intitulé</th>
                            <th scope="col" class="px-4 py-3 font-semibold">État</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Associations</th>
                            <th scope="col" class="px-4 py-3 font-semibold"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="item in items.data" :key="item.id">
                            <td class="whitespace-nowrap px-4 py-4 font-mono text-slate-800">{{ item.code }}</td>
                            <td class="px-4 py-4 font-medium text-slate-950">{{ item.name }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="item.is_active ? 'bg-emerald-100 text-emerald-950' : 'bg-slate-200 text-slate-800'">
                                    {{ item.is_active ? "Actif" : "Inactif" }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ item.associations_count }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <Link :href="`${baseUrl}/${item.id}/edit`" class="inline-flex min-h-10 items-center rounded px-3 text-sm font-semibold text-indigo-800 underline underline-offset-2 hover:text-indigo-950 focus-visible:outline-2 focus-visible:outline-indigo-700">
                                        Modifier
                                    </Link>
                                    <Link v-if="resource === 'matieres'" :href="`${baseUrl}/${item.id}/associations`" class="inline-flex min-h-10 items-center rounded px-3 text-sm font-semibold text-indigo-800 underline underline-offset-2 hover:text-indigo-950 focus-visible:outline-2 focus-visible:outline-indigo-700">
                                        Associations
                                    </Link>
                                    <button v-if="item.is_active" type="button" :disabled="processing" class="inline-flex min-h-10 items-center rounded px-3 text-sm font-semibold text-amber-900 underline underline-offset-2 hover:text-amber-950 focus-visible:outline-2 focus-visible:outline-amber-800 disabled:opacity-60" @click="deactivate(item)">
                                        Désactiver
                                    </button>
                                    <button v-else type="button" :disabled="processing" class="inline-flex min-h-10 items-center rounded px-3 text-sm font-semibold text-emerald-900 underline underline-offset-2 hover:text-emerald-950 focus-visible:outline-2 focus-visible:outline-emerald-800 disabled:opacity-60" @click="reactivate(item)">
                                        Réactiver
                                    </button>
                                    <button v-if="item.associations_count === 0" type="button" :disabled="processing" class="inline-flex min-h-10 items-center rounded px-3 text-sm font-semibold text-red-800 underline underline-offset-2 hover:text-red-950 focus-visible:outline-2 focus-visible:outline-red-800 disabled:opacity-60" @click="remove(item)">
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="p-8 text-center text-sm text-slate-700">
                Aucun résultat. Vous pouvez ajouter une valeur avec le bouton ci-dessus.
            </p>
        </section>

        <nav v-if="items.links.length > 3" aria-label="Pagination" class="flex flex-wrap justify-center gap-2">
            <button
                v-for="link in items.links"
                :key="link.label"
                type="button"
                :disabled="!link.url || processing"
                :aria-current="link.active ? 'page' : undefined"
                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border px-3 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                :class="link.active ? 'border-indigo-800 bg-indigo-800 text-white' : 'border-slate-400 text-slate-800 hover:bg-slate-100'"
                @click="visitPage(link.url)"
            >{{ link.label }}</button>
        </nav>
    </main>
</template>
