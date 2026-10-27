<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps<{
    title?: string;
    userName?: string | null;
    isAuthenticated?: boolean;
    canManageUsers?: boolean;
}>();

const logoutForm = useForm({});

function logout(): void {
    logoutForm.post("/logout");
}
</script>

<template>
    <Head :title="title ?? 'Bienvenue sur Academia'" />

    <main
        class="mx-auto flex min-h-svh max-w-5xl items-center px-4 py-10 sm:px-6 sm:py-16"
    >
        <section>
            <p class="text-sm font-semibold tracking-wide text-indigo-600">
                ACADEMIA
            </p>
            <h1
                class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl"
            >
                {{ title ?? "Bienvenue sur Academia" }}
            </h1>
            <p class="mt-4 max-w-2xl text-base text-slate-600 sm:text-lg">
                Votre espace de gestion est prêt à accueillir ses premiers
                modules.
            </p>
            <p v-if="userName" class="mt-4 text-sm text-slate-700">
                Connecté en tant que {{ userName }}.
            </p>
            <Link
                v-if="canManageUsers"
                href="/admin/utilisateurs"
                class="mt-6 inline-flex min-h-11 items-center rounded-lg border border-indigo-800 px-5 text-sm font-semibold text-indigo-900 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800"
            >
                Gérer les comptes
            </Link>
            <form v-if="isAuthenticated" class="mt-6" @submit.prevent="logout">
                <button
                    type="submit"
                    :disabled="logoutForm.processing"
                    class="inline-flex min-h-11 items-center rounded-lg bg-indigo-800 px-5 text-sm font-semibold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:opacity-70"
                >
                    Se déconnecter
                </button>
            </form>
        </section>
    </main>
</template>
