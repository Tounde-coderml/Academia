<script setup lang="ts">
import { Link, useForm } from "@inertiajs/vue3";
import AuthLayout from "../../components/AuthLayout.vue";

defineProps<{ email: string; status?: string; emailError?: string }>();

const form = useForm({ email: "" });

function resend(): void {
    form.post("/email/verification-notification");
}
</script>

<template>
    <AuthLayout
        title="Confirme ton adresse e-mail"
        description="Nous t’avons envoyé un lien de confirmation. Ouvre-le pour activer ton compte et accéder à ton tableau de bord."
        with-slideshow
    >
        <p class="mb-5 break-all text-sm font-medium text-slate-900">
            {{ email }}
        </p>

        <p
            v-if="status"
            role="status"
            class="mb-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900"
        >
            {{ status }}
        </p>

        <p
            v-if="emailError"
            role="alert"
            class="mb-5 rounded-lg border border-red-700 bg-red-50 p-3 text-sm text-red-900"
        >
            {{ emailError }}
        </p>

        <button
            type="button"
            :disabled="form.processing"
            class="min-h-12 w-full rounded-lg bg-indigo-800 px-4 py-3 text-base font-semibold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:cursor-wait disabled:opacity-70"
            @click="resend"
        >
            {{ form.processing ? "Envoi en cours…" : "Renvoyer le lien" }}
        </button>

        <p v-if="form.errors.email" role="alert" class="mt-3 text-sm text-red-800">
            {{ form.errors.email }}
        </p>

        <Link
            href="/logout"
            method="post"
            as="button"
            class="mt-4 inline-flex min-h-11 items-center rounded text-sm font-medium text-indigo-800 underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
        >
            Se déconnecter
        </Link>
    </AuthLayout>
</template>
