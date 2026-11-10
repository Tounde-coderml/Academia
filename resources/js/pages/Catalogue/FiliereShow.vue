<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";

defineProps<{
    filiere: { id: number; code: string; name: string };
    matieres: { id: number; name: string }[];
}>();
</script>

<template>
    <Head :title="`Filière ${filiere.name}`" />

    <main class="min-h-svh bg-slate-50 text-slate-900">
        <div class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
            <header class="border-b border-slate-300 pb-6">
                <Link
                    href="/epreuves"
                    class="text-sm font-semibold text-indigo-800 underline underline-offset-4"
                    >Retour au catalogue</Link
                >
                <p
                    class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-indigo-800"
                >
                    Filière {{ filiere.code }}
                </p>
                <h1
                    class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"
                >
                    {{ filiere.name }}
                </h1>
                <p class="mt-3 text-base leading-7 text-slate-600">
                    Choisis une matière pour consulter ses épreuves publiées.
                </p>
            </header>

            <section
                aria-label="Matières de la filière"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <Link
                    v-for="matiere in matieres"
                    :key="matiere.id"
                    :href="`/matieres/${matiere.id}/epreuves`"
                    class="group flex min-h-28 items-center justify-between gap-4 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-500 hover:bg-indigo-50/50 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                >
                    <span class="text-lg font-bold text-slate-950">{{
                        matiere.name
                    }}</span>
                    <span
                        class="text-lg font-semibold text-indigo-800"
                        aria-hidden="true"
                        >→</span
                    >
                </Link>
                <p
                    v-if="matieres.length === 0"
                    class="rounded-2xl border border-dashed border-slate-400 bg-white p-8 text-sm text-slate-600 sm:col-span-2 lg:col-span-3"
                >
                    Aucune matière active n’est associée à cette filière pour le
                    moment.
                </p>
            </section>
        </div>
    </main>
</template>
