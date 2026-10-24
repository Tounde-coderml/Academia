<script setup lang="ts">
import { Link, useForm } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";
import AuthLayout from "../../components/AuthLayout.vue";
import PasswordVisibilityToggle from "../../components/PasswordVisibilityToggle.vue";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});
const passwordVisible = ref(false);
const passwordConfirmationVisible = ref(false);
const errorSummary = ref<HTMLElement | null>(null);

function submit(): void {
    form.post("/register", {
        onError: () => {
            form.password = "";
            form.password_confirmation = "";
            nextTick(() => errorSummary.value?.focus());
        },
    });
}
</script>

<template>
    <AuthLayout
        title="Créer un compte"
        description="Inscris-toi pour accéder à ton espace Academia."
        with-slideshow
    >
        <div
            v-if="Object.keys(form.errors).length"
            ref="errorSummary"
            tabindex="-1"
            role="alert"
            aria-labelledby="error-summary-title"
            class="mb-5 rounded-lg border border-red-700 bg-red-50 p-4 text-sm text-red-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800"
        >
            <h2 id="error-summary-title" class="font-semibold">
                La création du compte a échoué
            </h2>
            <ul class="mt-2 list-inside list-disc">
                <li
                    v-for="[field, message] in Object.entries(form.errors)"
                    :key="field"
                >
                    <a :href="`#${field}`" class="underline underline-offset-2">
                        {{ message }}
                    </a>
                </li>
            </ul>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <label
                    for="name"
                    class="block text-sm font-medium text-slate-900"
                >
                    Nom complet
                </label>
                <input
                    id="name"
                    v-model="form.name"
                    name="name"
                    type="text"
                    autocomplete="name"
                    required
                    maxlength="255"
                    :aria-invalid="Boolean(form.errors.name)"
                    :aria-describedby="
                        form.errors.name ? 'name-error' : undefined
                    "
                    class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                />
                <p
                    v-if="form.errors.name"
                    id="name-error"
                    class="text-sm font-medium text-red-800"
                >
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="space-y-2">
                <label
                    for="email"
                    class="block text-sm font-medium text-slate-900"
                >
                    Adresse e-mail
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    name="email"
                    type="email"
                    autocomplete="email"
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

            <div class="space-y-2">
                <label
                    for="password"
                    class="block text-sm font-medium text-slate-900"
                >
                    Mot de passe
                </label>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        name="password"
                        :type="passwordVisible ? 'text' : 'password'"
                        autocomplete="new-password"
                        required
                        minlength="15"
                        :aria-invalid="Boolean(form.errors.password)"
                        :aria-describedby="
                            form.errors.password
                                ? 'password-hint password-error'
                                : 'password-hint'
                        "
                        class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 pr-12 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    />
                    <PasswordVisibilityToggle
                        v-model:visible="passwordVisible"
                        show-label="Afficher le mot de passe"
                        hide-label="Masquer le mot de passe"
                    />
                </div>
                <p id="password-hint" class="text-sm text-slate-700">
                    15 caractères minimum. Le collage est autorisé.
                </p>
                <p
                    v-if="form.errors.password"
                    id="password-error"
                    class="text-sm font-medium text-red-800"
                >
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="space-y-2">
                <label
                    for="password_confirmation"
                    class="block text-sm font-medium text-slate-900"
                >
                    Confirmer le mot de passe
                </label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        name="password_confirmation"
                        :type="
                            passwordConfirmationVisible ? 'text' : 'password'
                        "
                        autocomplete="new-password"
                        required
                        :aria-invalid="Boolean(form.errors.password)"
                        :aria-describedby="
                            form.errors.password ? 'password-error' : undefined
                        "
                        class="min-h-12 w-full rounded-lg border border-slate-500 bg-white px-3 pr-12 text-base text-slate-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
                    />
                    <PasswordVisibilityToggle
                        v-model:visible="passwordConfirmationVisible"
                        show-label="Afficher la confirmation du mot de passe"
                        hide-label="Masquer la confirmation du mot de passe"
                    />
                </div>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="min-h-12 w-full rounded-lg bg-indigo-800 px-4 py-3 text-base font-semibold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:cursor-wait disabled:opacity-70"
            >
                {{
                    form.processing ? "Création en cours…" : "Créer mon compte"
                }}
            </button>
        </form>

        <Link
            href="/login"
            class="mt-4 inline-flex min-h-11 items-center rounded text-sm font-medium text-indigo-800 underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
        >
            J’ai déjà un compte
        </Link>
    </AuthLayout>
</template>
