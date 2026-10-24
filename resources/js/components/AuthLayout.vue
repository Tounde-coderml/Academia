<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";

const props = defineProps<{
    title: string;
    description: string;
    withSlideshow?: boolean;
}>();

const slides = [
    {
        src: "/images/auth/students-campus.jpg",
        alt: "Des étudiants réunis sur le campus",
        caption: "Un espace pour apprendre et progresser ensemble.",
    },
    {
        src: "/images/auth/classroom.jpg",
        alt: "Des étudiants suivent un cours en amphithéâtre",
        caption: "Retrouve les ressources de ta communauté académique.",
    },
    {
        src: "/images/auth/studying.jpg",
        alt: "Une étudiante concentrée sur son travail",
        caption: "Construis ton parcours, une étape à la fois.",
    },
];
const activeSlide = ref(0);
let slideInterval: ReturnType<typeof setInterval> | undefined;

function showSlide(index: number): void {
    activeSlide.value = index;
}

onMounted(() => {
    if (
        props.withSlideshow &&
        !window.matchMedia("(prefers-reduced-motion: reduce)").matches
    ) {
        slideInterval = setInterval(() => {
            activeSlide.value = (activeSlide.value + 1) % slides.length;
        }, 3000);
    }
});

onUnmounted(() => {
    if (slideInterval !== undefined) {
        clearInterval(slideInterval);
    }
});
</script>

<template>
    <Head :title="title" />

    <main
        :class="
            withSlideshow
                ? 'grid min-h-svh bg-slate-100 lg:grid-cols-2'
                : 'flex min-h-svh items-center justify-center bg-slate-100 px-4 py-10'
        "
    >
        <section
            aria-labelledby="page-title"
            :class="
                withSlideshow
                    ? 'mx-auto flex w-full max-w-2xl items-center px-4 py-8 sm:px-8 lg:min-h-svh lg:px-12 lg:py-12'
                    : 'w-full max-w-md rounded-2xl bg-white p-6 shadow-lg sm:p-8'
            "
        >
            <div
                :class="
                    withSlideshow
                        ? 'w-full rounded-2xl bg-white p-6 shadow-lg sm:p-8 lg:shadow-xl'
                        : ''
                "
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
            </div>
        </section>

        <aside
            v-if="withSlideshow"
            aria-label="Photos de la vie académique"
            class="relative order-last min-h-64 overflow-hidden bg-slate-900 sm:min-h-80 lg:order-none lg:min-h-svh"
        >
            <img
                v-for="(slide, index) in slides"
                :key="slide.src"
                :src="slide.src"
                :alt="slide.alt"
                :aria-hidden="activeSlide !== index"
                loading="eager"
                class="absolute inset-0 h-full w-full object-cover transition-opacity duration-700 motion-reduce:transition-none"
                :class="activeSlide === index ? 'opacity-100' : 'opacity-0'"
            />
            <div
                aria-hidden="true"
                class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/10 to-slate-950/10"
            />

            <div
                class="absolute inset-x-0 bottom-16 flex items-end justify-between gap-4 p-5 pb-10 text-white sm:bottom-20 sm:p-8 sm:pb-14 lg:bottom-24 lg:p-10 lg:pb-16"
            >
                <p
                    class="max-w-md text-lg font-semibold leading-7 sm:text-2xl sm:leading-8"
                    aria-live="off"
                >
                    {{ slides[activeSlide].caption }}
                </p>

                <div
                    class="flex shrink-0 items-center gap-1.5"
                    aria-label="Choisir une photo"
                >
                    <button
                        v-for="(slide, index) in slides"
                        :key="slide.src"
                        type="button"
                        :aria-label="`Afficher la photo ${index + 1} sur ${slides.length}`"
                        :aria-current="
                            activeSlide === index ? 'true' : undefined
                        "
                        class="inline-flex min-h-11 min-w-8 items-center justify-center rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                        @click="showSlide(index)"
                    >
                        <span
                            class="h-2.5 w-2.5 rounded-full ring-1 ring-white/80"
                            :class="
                                activeSlide === index
                                    ? 'bg-white'
                                    : 'bg-white/40'
                            "
                        />
                    </button>
                </div>
            </div>
        </aside>
    </main>
</template>
