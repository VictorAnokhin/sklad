import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
            // refresh: ["resources/views/**"],
        }),
    ],
    server: {
        watch: {
            usePolling: true,
        },
        host: true,
        hmr: {
            host: "localhost",
        },
        strictPort: true,
        port: 80,
    },
});
