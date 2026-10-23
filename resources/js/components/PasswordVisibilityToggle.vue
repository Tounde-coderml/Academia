<script setup lang="ts">
defineProps<{
    visible: boolean;
    showLabel: string;
    hideLabel: string;
}>();

const emit = defineEmits<{
    "update:visible": [visible: boolean];
}>();
</script>

<template>
    <button
        type="button"
        :aria-label="visible ? hideLabel : showLabel"
        :aria-pressed="visible"
        class="absolute inset-y-0 right-0 inline-flex min-h-12 w-12 items-center justify-center rounded-r-lg text-slate-700 hover:text-indigo-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700"
        @click="emit('update:visible', !visible)"
    >
        <svg
            v-if="visible"
            aria-hidden="true"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            class="h-5 w-5"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3 3l18 18M10.6 10.7a2 2 0 002.7 2.7M9.9 5.2A10.9 10.9 0 0112 5c5 0 8.5 4 9.5 7-.4 1.3-1.3 2.6-2.5 3.7M6.2 6.2C4.1 7.5 2.9 9.4 2.5 12c.7 2.3 3.3 5.2 7 6.5"
            />
        </svg>
        <svg
            v-else
            aria-hidden="true"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            class="h-5 w-5"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M2.5 12C3.5 9 7 5 12 5s8.5 4 9.5 7c-1 3-4.5 7-9.5 7s-8.5-4-9.5-7z"
            />
            <circle cx="12" cy="12" r="3" />
        </svg>
    </button>
</template>
