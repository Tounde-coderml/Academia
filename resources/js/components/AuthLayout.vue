<script setup lang="ts">
import { Head } from "@inertiajs/vue3";

defineProps<{
    title: string;
    description: string;
}>();
</script>

<template>
    <Head :title="title" />

    <main
        class="flex min-h-svh items-center justify-center bg-slate-100 px-4 py-10"
    >
        <section
            aria-labelledby="page-title"
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-lg sm:p-8"
        >
            <a
                href="/"
                class="inline-flex min-h-11 items-center rounded text-sm font-semibold tracking-wide text-indigo-700 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-700"
            >
                ACADEMIA
            </a>

            <h1
                id="page-title"
                class="mt-5 text-2xl font-bold tracking-tight text-slate-950"
            >
                {{ title }}
            </h1>
            <p class="mt-2 text-sm leading-6 text-slate-700">
                {{ description }}
            </p>

            <div class="mt-7">
                <slot />
            </div>
        </section>
    </main>
</template>
