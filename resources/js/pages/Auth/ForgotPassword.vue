<script setup lang="ts">
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";
import AuthLayout from "../../components/AuthLayout.vue";

const form = useForm({ email: "" });
const errorSummary = ref<HTMLElement | null>(null);
const page = usePage<{ flash: { status?: string } }>();

function submit(): void {
    form.post("/forgot-password", {
        onError: () => {
            nextTick(() => errorSummary.value?.focus());
        },
    });
}
</script>

<template>
    <AuthLayout
        title="Réinitialiser le mot de passe"
        description="Indique l’adresse e-mail du compte. Si elle correspond à un compte, tu recevras un lien de réinitialisation."
        with-slideshow
    >
        <p
            v-if="page.props.flash.status"
            role="status"
            class="mb-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900"
        >
            {{ page.props.flash.status }}
        </p>

        <div
            v-if="form.errors.email"
            ref="errorSummary"
            tabindex="-1"
            role="alert"
            aria-labelledby="error-summary-title"
            class="mb-5 rounded-lg border border-red-700 bg-red-50 p-4 text-sm text-red-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800"
        >
            <h2 id="error-summary-title" class="font-semibold">
                Vérifie l’adresse e-mail
            </h2>
            <a
                href="#email"
                class="mt-2 inline-flex min-h-11 items-center underline underline-offset-2"
            >
                {{ form.errors.email }}
            </a>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <label
                    for="email"
                    class="block text-sm font-medium text-slate-900"
                    >Adresse e-mail</label
                >
                <input
                    id="email"
                    v-model="form.email"
                    name="email"
                    type="email"
                    autocomplete="username"
                    autocapitalize="none"
                    spellcheck="false"
                    inputmode="email"
                    required
                    maxlength="254"
                    :aria-invalid="Boolean(form.errors.email)"
                    :aria-describedby="
                        form.errors.email ? 'email-error' : undefined
                    "
                    class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                />
                <p
                    v-if="form.errors.email"
                    id="email-error"
                    class="text-sm font-medium text-red-800"
                >
                    {{ form.errors.email }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="min-h-12 w-full rounded-lg bg-indigo-800 px-4 py-3 text-base font-semibold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:cursor-wait disabled:opacity-70"
            >
                {{ form.processing ? "Envoi en cours…" : "Envoyer le lien" }}
            </button>
        </form>

        <Link
            href="/login"
            class="mt-4 inline-flex min-h-11 items-center rounded text-sm font-medium text-indigo-800 underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
        >
            Retour à la connexion
        </Link>
    </AuthLayout>
</template>
