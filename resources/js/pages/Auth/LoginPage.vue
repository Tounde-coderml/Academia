<script setup lang="ts">
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, nextTick, ref } from "vue";
import AuthLayout from "../../components/AuthLayout.vue";
import PasswordVisibilityToggle from "../../components/PasswordVisibilityToggle.vue";

const form = useForm({
    email: "",
    password: "",
});
const passwordVisible = ref(false);
const errorSummary = ref<HTMLElement | null>(null);
const errors = computed(() => Object.entries(form.errors));
const page = usePage<{ flash: { status?: string } }>();

function submit(): void {
    form.post("/login", {
        onError: () => {
            form.password = "";
            nextTick(() => errorSummary.value?.focus());
        },
    });
}
</script>

<template>
    <AuthLayout
        title="Connexion"
        description="Connecte-toi à ton espace Academia avec ton adresse e-mail et ton mot de passe."
    >
        <p
            v-if="page.props.flash.status"
            role="status"
            class="mb-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900"
        >
            {{ page.props.flash.status }}
        </p>

        <div
            v-if="errors.length"
            ref="errorSummary"
            tabindex="-1"
            role="alert"
            aria-labelledby="error-summary-title"
            class="mb-5 rounded-lg border border-red-700 bg-red-50 p-4 text-sm text-red-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800"
        >
            <h2 id="error-summary-title" class="font-semibold">
                La connexion a échoué
            </h2>
            <ul class="mt-2 list-inside list-disc">
                <li v-for="[field, message] in errors" :key="field">
                    <a
                        :href="`#${field}`"
                        class="underline underline-offset-2"
                        >{{ message }}</a
                    >
                </li>
            </ul>
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
                    class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 text-base text-slate-950 placeholder:text-slate-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                />
                <p
                    v-if="form.errors.email"
                    id="email-error"
                    class="text-sm font-medium text-red-800"
                >
                    {{ form.errors.email }}
                </p>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <label
                        for="password"
                        class="block text-sm font-medium text-slate-900"
                        >Mot de passe</label
                    >
                    <Link
                        href="/forgot-password"
                        class="inline-flex min-h-11 items-center rounded text-sm font-medium text-indigo-800 underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    >
                        Mot de passe oublié ?
                    </Link>
                </div>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        name="password"
                        :type="passwordVisible ? 'text' : 'password'"
                        autocomplete="current-password"
                        required
                        :aria-invalid="Boolean(form.errors.password)"
                        :aria-describedby="
                            form.errors.password ? 'password-error' : undefined
                        "
                        class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 pr-12 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    />
                    <PasswordVisibilityToggle
                        v-model:visible="passwordVisible"
                        show-label="Afficher le mot de passe"
                        hide-label="Masquer le mot de passe"
                    />
                </div>
                <p
                    v-if="form.errors.password"
                    id="password-error"
                    class="text-sm font-medium text-red-800"
                >
                    {{ form.errors.password }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="min-h-12 w-full rounded-lg bg-indigo-800 px-4 py-3 text-base font-semibold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:cursor-wait disabled:opacity-70"
            >
                {{ form.processing ? "Connexion en cours…" : "Se connecter" }}
            </button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-700">
            Pas encore de compte ?
            <Link
                href="/register"
                class="font-medium text-indigo-800 underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
            >
                Créer un compte
            </Link>
        </p>
    </AuthLayout>
</template>
