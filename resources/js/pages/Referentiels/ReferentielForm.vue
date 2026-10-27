<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";

type Resource = "filieres" | "niveaux" | "matieres";

interface ReferentialItem {
    id: number;
    code: string;
    name: string;
    sort_order?: number;
    code_locked: boolean;
}

const props = defineProps<{
    title: string;
    resource: Resource;
    item: ReferentialItem | null;
}>();

const form = useForm({
    code: props.item?.code ?? "",
    name: props.item?.name ?? "",
    sort_order: props.item?.sort_order ?? 0,
});
const errorSummary = ref<HTMLElement | null>(null);
const networkError = ref(false);
const baseUrl = `/admin/${props.resource}`;

function submit(): void {
    networkError.value = false;
    form.transform((data) =>
        props.resource === "niveaux"
            ? data
            : { code: data.code, name: data.name },
    );

    if (props.item) {
        form.put(`${baseUrl}/${props.item.id}`, {
            onError: () => nextTick(() => errorSummary.value?.focus()),
            onNetworkError: () => (networkError.value = true),
        });

        return;
    }

    form.post(baseUrl, {
        onError: () => nextTick(() => errorSummary.value?.focus()),
        onNetworkError: () => (networkError.value = true),
    });
}
</script>

<template>
    <Head :title="title" />

    <main class="mx-auto flex min-h-svh w-full max-w-3xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <header>
            <Link :href="baseUrl" class="inline-flex min-h-11 items-center rounded text-sm font-semibold text-indigo-800 underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-700">
                ← Retour aux référentiels
            </Link>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950">{{ title }}</h1>
            <p class="mt-2 text-sm text-slate-700">Les valeurs inactives restent conservées pour l’historique.</p>
        </header>

        <div
            v-if="Object.keys(form.errors).length"
            ref="errorSummary"
            tabindex="-1"
            role="alert"
            aria-labelledby="error-summary-title"
            class="rounded-lg border border-red-700 bg-red-50 p-4 text-sm text-red-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800"
        >
            <h2 id="error-summary-title" class="font-semibold">La sauvegarde a échoué</h2>
            <ul class="mt-2 list-inside list-disc">
                <li v-for="[field, message] in Object.entries(form.errors)" :key="field">
                    <a :href="`#${field}`" class="underline underline-offset-2">{{ message }}</a>
                </li>
            </ul>
        </div>
        <div v-if="networkError" role="alert" class="flex flex-col gap-3 rounded-lg border border-red-700 bg-red-50 p-4 text-sm text-red-900 sm:flex-row sm:items-center sm:justify-between">
            <p>La sauvegarde n’a pas abouti à cause d’un problème réseau. Votre saisie est conservée.</p>
            <button type="button" :disabled="form.processing" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-red-800 px-4 font-semibold hover:bg-red-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800 disabled:opacity-60" @click="submit">
                Réessayer
            </button>
        </div>

        <form class="flex flex-col gap-5 rounded-xl border border-slate-300 bg-white p-5 sm:p-7" @submit.prevent="submit">
            <div class="space-y-2">
                <label for="code" class="block text-sm font-medium text-slate-900">Code</label>
                <input
                    id="code"
                    v-model="form.code"
                    name="code"
                    type="text"
                    required
                    maxlength="255"
                    :readonly="item?.code_locked"
                    :aria-invalid="Boolean(form.errors.code)"
                    :aria-describedby="form.errors.code ? 'code-error' : item?.code_locked ? 'code-help' : undefined"
                    class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700 read-only:bg-slate-100"
                />
                <p v-if="form.errors.code" id="code-error" class="text-sm font-medium text-red-800">{{ form.errors.code }}</p>
                <p v-else-if="item?.code_locked" id="code-help" class="text-sm text-slate-700">Ce code est verrouillé car la valeur est déjà utilisée.</p>
            </div>

            <div class="space-y-2">
                <label for="name" class="block text-sm font-medium text-slate-900">Intitulé</label>
                <input
                    id="name"
                    v-model="form.name"
                    name="name"
                    type="text"
                    required
                    maxlength="255"
                    :aria-invalid="Boolean(form.errors.name)"
                    :aria-describedby="form.errors.name ? 'name-error' : undefined"
                    class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                />
                <p v-if="form.errors.name" id="name-error" class="text-sm font-medium text-red-800">{{ form.errors.name }}</p>
            </div>

            <div v-if="resource === 'niveaux'" class="space-y-2">
                <label for="sort_order" class="block text-sm font-medium text-slate-900">Ordre d’affichage</label>
                <input
                    id="sort_order"
                    v-model.number="form.sort_order"
                    name="sort_order"
                    type="number"
                    min="0"
                    max="65535"
                    required
                    :aria-invalid="Boolean(form.errors.sort_order)"
                    :aria-describedby="form.errors.sort_order ? 'sort-order-error' : undefined"
                    class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                />
                <p v-if="form.errors.sort_order" id="sort-order-error" class="text-sm font-medium text-red-800">{{ form.errors.sort_order }}</p>
            </div>

            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                <Link :href="baseUrl" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-500 px-5 text-sm font-semibold text-slate-900 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">
                    Annuler
                </Link>
                <button type="submit" :disabled="form.processing" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-800 px-5 text-sm font-semibold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:cursor-wait disabled:opacity-70">
                    {{ form.processing ? "Enregistrement…" : "Enregistrer" }}
                </button>
            </div>
        </form>
    </main>
</template>
