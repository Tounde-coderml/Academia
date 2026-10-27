<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

defineOptions({ name: "UsersManagementPage" });

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    is_active: boolean;
    is_deleted: boolean;
    created_at: string | null;
};

type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type AuditEntry = {
    action: string;
    actor: string;
    user: string;
    details: Record<string, unknown>;
    created_at: string;
};

const props = defineProps<{
    users: {
        data: ManagedUser[];
        links: PageLink[];
        current_page: number;
        last_page: number;
    };
    roles: { value: string; label: string }[];
    filters: { search: string; include_deleted: boolean };
    audits: AuditEntry[];
    flash: { status?: string | null };
    errors: Record<string, string>;
}>();

const search = ref(props.filters.search);
const includeDeleted = ref(props.filters.include_deleted);
const editingUser = ref<ManagedUser | null>(null);
const createForm = useForm({
    name: "",
    email: "",
    role: "etudiant",
});
const editForm = useForm({
    name: "",
    email: "",
    role: "etudiant",
});

function applyFilters(): void {
    router.get(
        "/admin/utilisateurs",
        { search: search.value, include_deleted: includeDeleted.value ? 1 : 0 },
        { preserveState: true, replace: true },
    );
}

function editUser(user: ManagedUser): void {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.role ?? "etudiant";
}

function cancelEdit(): void {
    editingUser.value = null;
    editForm.clearErrors();
}

function saveUser(): void {
    if (!editingUser.value) {
        return;
    }

    editForm.put(`/admin/utilisateurs/${editingUser.value.id}`, {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
}

function updateStatus(user: ManagedUser): void {
    router.patch(
        `/admin/utilisateurs/${user.id}/statut`,
        { is_active: !user.is_active },
        { preserveScroll: true },
    );
}

function deleteUser(user: ManagedUser): void {
    if (window.confirm(`Supprimer logiquement le compte de ${user.name} ?`)) {
        router.delete(`/admin/utilisateurs/${user.id}`, {
            preserveScroll: true,
        });
    }
}

function restoreUser(user: ManagedUser): void {
    router.patch(
        `/admin/utilisateurs/${user.id}/restaurer`,
        {},
        { preserveScroll: true },
    );
}

function auditSummary(details: Record<string, unknown>): string {
    const fields = details.fields;

    if (
        Array.isArray(fields) &&
        fields.every((field) => typeof field === "string")
    ) {
        return fields.join(", ");
    }

    if (
        typeof details.previous_role === "string" &&
        typeof details.role === "string"
    ) {
        return `${details.previous_role} → ${details.role}`;
    }

    if (typeof details.role === "string") {
        return details.role;
    }

    if (typeof details.is_active === "boolean") {
        return details.is_active ? "Compte activé" : "Compte désactivé";
    }

    return "";
}
</script>

<template>
    <Head title="Gestion des comptes" />

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <Link
                    href="/dashboard"
                    class="text-sm font-semibold text-indigo-800 underline underline-offset-4"
                >
                    Retour à l’espace Academia
                </Link>
                <h1
                    class="mt-3 text-3xl font-bold tracking-tight text-slate-950"
                >
                    Gestion des comptes
                </h1>
                <p class="mt-2 text-sm text-slate-700">
                    Crée les comptes et gère leur rôle et leur accès.
                </p>
            </div>
        </header>

        <p
            v-if="flash.status"
            role="status"
            class="mb-6 rounded-lg border border-green-700 bg-green-50 p-4 text-sm font-medium text-green-950"
        >
            {{ flash.status }}
        </p>
        <div
            v-if="Object.keys(errors).length"
            role="alert"
            class="mb-6 rounded-lg border border-red-700 bg-red-50 p-4 text-sm text-red-900"
        >
            <p class="font-semibold">L’action n’a pas pu être effectuée.</p>
            <ul class="mt-2 list-inside list-disc">
                <li v-for="(message, field) in errors" :key="field">
                    {{ message }}
                </li>
            </ul>
        </div>

        <section
            aria-labelledby="create-user-title"
            class="mb-8 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm sm:p-6"
        >
            <h2
                id="create-user-title"
                class="text-xl font-semibold text-slate-950"
            >
                Créer un compte
            </h2>
            <p class="mt-1 text-sm text-slate-700">
                Un lien de définition du mot de passe sera envoyé à la nouvelle
                adresse e-mail.
            </p>

            <form
                class="mt-5 grid gap-4 md:grid-cols-2"
                @submit.prevent="
                    createForm.post('/admin/utilisateurs', {
                        preserveScroll: true,
                        onSuccess: () => createForm.reset(),
                    })
                "
            >
                <div>
                    <label
                        for="new-name"
                        class="mb-1 block text-sm font-medium"
                    >
                        Nom complet
                    </label>
                    <input
                        id="new-name"
                        v-model="createForm.name"
                        autocomplete="name"
                        required
                        maxlength="255"
                        class="min-h-11 w-full rounded-lg border border-slate-500 px-3"
                        :aria-invalid="Boolean(createForm.errors.name)"
                    />
                    <p
                        v-if="createForm.errors.name"
                        class="mt-1 text-sm text-red-800"
                    >
                        {{ createForm.errors.name }}
                    </p>
                </div>

                <div>
                    <label
                        for="new-email"
                        class="mb-1 block text-sm font-medium"
                    >
                        Adresse e-mail
                    </label>
                    <input
                        id="new-email"
                        v-model="createForm.email"
                        type="email"
                        autocomplete="email"
                        required
                        maxlength="254"
                        class="min-h-11 w-full rounded-lg border border-slate-500 px-3"
                        :aria-invalid="Boolean(createForm.errors.email)"
                    />
                    <p
                        v-if="createForm.errors.email"
                        class="mt-1 text-sm text-red-800"
                    >
                        {{ createForm.errors.email }}
                    </p>
                </div>

                <div>
                    <label
                        for="new-role"
                        class="mb-1 block text-sm font-medium"
                    >
                        Rôle initial
                    </label>
                    <select
                        id="new-role"
                        v-model="createForm.role"
                        class="min-h-11 w-full rounded-lg border border-slate-500 bg-white px-3"
                    >
                        <option
                            v-for="role in roles"
                            :key="role.value"
                            :value="role.value"
                        >
                            {{ role.label }}
                        </option>
                    </select>
                    <p
                        v-if="createForm.errors.role"
                        class="mt-1 text-sm text-red-800"
                    >
                        {{ createForm.errors.role }}
                    </p>
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="inline-flex min-h-11 items-center rounded-lg bg-indigo-800 px-5 font-semibold text-white hover:bg-indigo-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-800 disabled:opacity-60"
                    >
                        {{
                            createForm.processing
                                ? "Création…"
                                : "Créer le compte"
                        }}
                    </button>
                </div>
            </form>
        </section>

        <section
            v-if="editingUser"
            aria-labelledby="edit-user-title"
            class="mb-8 rounded-2xl border border-indigo-300 bg-indigo-50 p-5 sm:p-6"
        >
            <h2
                id="edit-user-title"
                class="text-xl font-semibold text-slate-950"
            >
                Modifier {{ editingUser.name }}
            </h2>
            <form
                class="mt-5 grid gap-4 md:grid-cols-2"
                @submit.prevent="saveUser"
            >
                <div>
                    <label
                        for="edit-name"
                        class="mb-1 block text-sm font-medium"
                    >
                        Nom complet
                    </label>
                    <input
                        id="edit-name"
                        v-model="editForm.name"
                        required
                        maxlength="255"
                        class="min-h-11 w-full rounded-lg border border-slate-500 bg-white px-3"
                    />
                    <p
                        v-if="editForm.errors.name"
                        class="mt-1 text-sm text-red-800"
                    >
                        {{ editForm.errors.name }}
                    </p>
                </div>

                <div>
                    <label
                        for="edit-email"
                        class="mb-1 block text-sm font-medium"
                    >
                        Adresse e-mail
                    </label>
                    <input
                        id="edit-email"
                        v-model="editForm.email"
                        type="email"
                        required
                        maxlength="254"
                        class="min-h-11 w-full rounded-lg border border-slate-500 bg-white px-3"
                    />
                    <p
                        v-if="editForm.errors.email"
                        class="mt-1 text-sm text-red-800"
                    >
                        {{ editForm.errors.email }}
                    </p>
                </div>

                <div>
                    <label
                        for="edit-role"
                        class="mb-1 block text-sm font-medium"
                    >
                        Rôle
                    </label>
                    <select
                        id="edit-role"
                        v-model="editForm.role"
                        class="min-h-11 w-full rounded-lg border border-slate-500 bg-white px-3"
                    >
                        <option
                            v-for="role in roles"
                            :key="role.value"
                            :value="role.value"
                        >
                            {{ role.label }}
                        </option>
                    </select>
                    <p
                        v-if="editForm.errors.role"
                        class="mt-1 text-sm text-red-800"
                    >
                        {{ editForm.errors.role }}
                    </p>
                </div>

                <div class="flex items-end gap-3">
                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="min-h-11 rounded-lg bg-indigo-800 px-5 font-semibold text-white hover:bg-indigo-900 disabled:opacity-60"
                    >
                        Enregistrer
                    </button>
                    <button
                        type="button"
                        class="min-h-11 rounded-lg border border-slate-500 px-4 font-semibold"
                        @click="cancelEdit"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </section>

        <section
            aria-labelledby="users-title"
            class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm sm:p-6"
        >
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2
                        id="users-title"
                        class="text-xl font-semibold text-slate-950"
                    >
                        Comptes utilisateurs
                    </h2>
                    <p class="mt-1 text-sm text-slate-700">
                        {{ users.data.length }} compte(s) sur cette page.
                    </p>
                </div>
                <form
                    class="flex flex-wrap items-end gap-3"
                    @submit.prevent="applyFilters"
                >
                    <div>
                        <label
                            for="user-search"
                            class="mb-1 block text-sm font-medium"
                        >
                            Rechercher
                        </label>
                        <input
                            id="user-search"
                            v-model="search"
                            type="search"
                            maxlength="100"
                            class="min-h-11 rounded-lg border border-slate-500 px-3"
                        />
                    </div>
                    <label class="flex min-h-11 items-center gap-2 text-sm">
                        <input
                            v-model="includeDeleted"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-500"
                        />
                        Inclure les comptes supprimés
                    </label>
                    <button
                        type="submit"
                        class="min-h-11 rounded-lg border border-indigo-800 px-4 font-semibold text-indigo-900"
                    >
                        Filtrer
                    </button>
                </form>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table
                    class="w-full min-w-[760px] border-collapse text-left text-sm"
                >
                    <thead>
                        <tr class="border-b border-slate-300 text-slate-700">
                            <th scope="col" class="px-3 py-3 font-semibold">
                                Nom
                            </th>
                            <th scope="col" class="px-3 py-3 font-semibold">
                                E-mail
                            </th>
                            <th scope="col" class="px-3 py-3 font-semibold">
                                Rôle
                            </th>
                            <th scope="col" class="px-3 py-3 font-semibold">
                                État
                            </th>
                            <th scope="col" class="px-3 py-3 font-semibold">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b border-slate-200 align-top"
                        >
                            <td class="px-3 py-3 font-medium">
                                {{ user.name }}
                            </td>
                            <td class="px-3 py-3">{{ user.email }}</td>
                            <td class="px-3 py-3">
                                {{
                                    roles.find(
                                        (role) => role.value === user.role,
                                    )?.label ?? "Aucun rôle"
                                }}
                            </td>
                            <td class="px-3 py-3">
                                <span v-if="user.is_deleted">Supprimé</span>
                                <span v-else-if="user.is_active">Actif</span>
                                <span v-else>Inactif</span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-if="!user.is_deleted"
                                        type="button"
                                        class="min-h-9 rounded border border-indigo-800 px-3 text-indigo-900 underline-offset-2 hover:underline"
                                        @click="editUser(user)"
                                    >
                                        Modifier
                                    </button>
                                    <button
                                        v-if="!user.is_deleted"
                                        type="button"
                                        class="min-h-9 rounded border border-slate-500 px-3 underline-offset-2 hover:underline"
                                        @click="updateStatus(user)"
                                    >
                                        {{
                                            user.is_active
                                                ? "Désactiver"
                                                : "Activer"
                                        }}
                                    </button>
                                    <button
                                        v-if="!user.is_deleted"
                                        type="button"
                                        class="min-h-9 rounded border border-red-800 px-3 text-red-900 underline-offset-2 hover:underline"
                                        @click="deleteUser(user)"
                                    >
                                        Supprimer
                                    </button>
                                    <button
                                        v-if="user.is_deleted"
                                        type="button"
                                        class="min-h-9 rounded border border-green-800 px-3 text-green-900 underline-offset-2 hover:underline"
                                        @click="restoreUser(user)"
                                    >
                                        Restaurer
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td
                                colspan="5"
                                class="px-3 py-8 text-center text-slate-700"
                            >
                                Aucun compte ne correspond à ces critères.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav
                v-if="users.last_page > 1"
                aria-label="Pagination des comptes"
                class="mt-5 flex flex-wrap gap-2"
            >
                <template
                    v-for="link in users.links"
                    :key="`${link.label}-${link.url}`"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :aria-current="link.active ? 'page' : undefined"
                        class="inline-flex min-h-10 items-center rounded border px-3 text-sm"
                        :class="
                            link.active
                                ? 'border-indigo-800 bg-indigo-800 text-white'
                                : 'border-slate-400 text-slate-900'
                        "
                    >
                        {{ link.label.replace(/&laquo;|&raquo;/g, "") }}
                    </Link>
                    <span
                        v-else
                        aria-disabled="true"
                        class="inline-flex min-h-10 items-center rounded border border-slate-300 px-3 text-sm text-slate-500"
                    >
                        {{ link.label.replace(/&laquo;|&raquo;/g, "") }}
                    </span>
                </template>
            </nav>
        </section>

        <section
            aria-labelledby="audit-title"
            class="mt-8 rounded-2xl border border-slate-300 bg-white p-5 shadow-sm sm:p-6"
        >
            <h2 id="audit-title" class="text-xl font-semibold text-slate-950">
                Changements récents
            </h2>
            <p class="mt-1 text-sm text-slate-700">
                Les journaux ne contiennent pas de mots de passe ni de valeurs
                personnelles avant/après.
            </p>
            <ol v-if="audits.length" class="mt-4 divide-y divide-slate-200">
                <li
                    v-for="(audit, index) in audits"
                    :key="`${audit.created_at}-${audit.action}-${index}`"
                    class="py-3 text-sm"
                >
                    <span class="font-semibold">{{ audit.actor }}</span>
                    — {{ audit.action }} — compte de
                    <span class="font-semibold">{{ audit.user }}</span>
                    <span v-if="auditSummary(audit.details)">
                        ({{ auditSummary(audit.details) }})
                    </span>
                    <time class="ml-2 text-slate-600">{{
                        audit.created_at
                    }}</time>
                </li>
            </ol>
            <p v-else class="mt-4 text-sm text-slate-700">
                Aucun changement enregistré.
            </p>
        </section>
    </main>
</template>
