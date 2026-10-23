import { defineConfig } from "vitest/config";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.ts"],
            refresh: true,
        }),
        tailwindcss(),
    ],
    test: {
        environment: "jsdom",
        include: ["resources/js/**/*.test.ts"],
    },
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
