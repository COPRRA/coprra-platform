import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";
import { VitePWA } from "vite-plugin-pwa";
import { visualizer } from "rollup-plugin-visualizer";
import { analyzer } from "vite-bundle-analyzer";

const ANALYZE = process.env.ANALYZE === "true";
const IS_PRODUCTION = process.env.NODE_ENV === "production";
const IS_DEVELOPMENT = process.env.NODE_ENV === "development";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        VitePWA({
            registerType: "autoUpdate",
            includeAssets: ["favicon.ico", "robots.txt", "apple-touch-icon.png"],
            manifest: {
                name: "COPRRA",
                short_name: "COPRRA",
                description: "COPRRA Progressive Web App",
                theme_color: "#ffffff",
                background_color: "#ffffff",
                display: "standalone",
                scope: "/",
                start_url: "/",
                icons: [
                    { src: "/icons/icon-192x192.png", sizes: "192x192", type: "image/png" },
                    { src: "/icons/icon-512x512.png", sizes: "512x512", type: "image/png" },
                    { src: "/icons/icon-512x512.png", sizes: "512x512", type: "image/png", purpose: "any maskable" },
                ],
            },
            workbox: {
                clientsClaim: true,
                skipWaiting: true,
                cleanupOutdatedCaches: true,
                globPatterns: ["**/*.{js,css,html,svg,png,jpg,jpeg,webp}"]
            },
            devOptions: {
                enabled: true
            }
        }),
        // Enable Vite Bundle Analyzer in static mode without opening a browser
        analyzer({ analyzerMode: "static", openAnalyzer: false, fileName: "analyzer", defaultSizes: "gzip", summary: true, enabled: ANALYZE }),
    ],

    // Build optimization with maximum strictness
    build: {
        // Output directory for production build
        outDir: "public/build",
        emptyOutDir: true,
        
        // Enable source maps for debugging (disable in production for security)
        sourcemap: IS_DEVELOPMENT || ANALYZE,

        // Strict minification settings
        minify: IS_PRODUCTION ? "terser" : false,
        terserOptions: {
            compress: {
                drop_console: IS_PRODUCTION,
                drop_debugger: true,
                pure_funcs: IS_PRODUCTION ? ["console.log", "console.info", "console.debug", "console.warn"] : [],
                passes: 2,
                unsafe: false,
                unsafe_comps: false,
                unsafe_Function: false,
                unsafe_math: false,
                unsafe_symbols: false,
                unsafe_methods: false,
                unsafe_proto: false,
                unsafe_regexp: false,
                unsafe_undefined: false,
            },
            mangle: {
                safari10: true,
                keep_classnames: false,
                keep_fnames: false,
            },
            format: {
                comments: false,
            },
        },

        // Strict asset handling
        assetsInlineLimit: 2048, // Reduced for better caching
        assetsDir: "assets",

        // Strict chunk size warnings
        chunkSizeWarningLimit: 500, // More strict limit

        // Report compressed size
        reportCompressedSize: true,
        
        // CSS code splitting
        cssCodeSplit: true,
        
        // Target modern browsers for better optimization
        target: ["es2020", "chrome80", "firefox78", "safari14"],

        // Code splitting for better caching
        rollupOptions: {
            output: {
                // Group commonly used dependencies into a single vendor chunk only when used
                manualChunks: (id) => {
                    if (id.includes("node_modules")) {
                        if (/axios|alpinejs|lodash/.test(id)) {
                            return "vendor";
                        }
                    }
                    // Let Rollup decide for other modules to avoid empty chunks
                },
            },
            // Add bundle analyzer (visualizer) only when ANALYZE is true
            plugins: [
                ...(ANALYZE
                    ? [
                        visualizer({
                            filename: "public/build/stats.html",
                            template: "treemap",
                            gzipSize: true,
                            brotliSize: true,
                        }),
                    ]
                    : []
                ),
            ],
        },
    },

    // Development server with enhanced security
    server: {
        host: IS_DEVELOPMENT ? "localhost" : "0.0.0.0",
        port: 5173,
        strictPort: true,
        open: false, // Don't auto-open browser for security
        cors: {
            origin: IS_DEVELOPMENT ? ["http://localhost:8000", "http://127.0.0.1:8000"] : false,
            credentials: true,
        },
        fs: {
            strict: true,
            allow: [".."], // Restrict file system access
            deny: [".env", ".env.*", "*.log"],
        },
        hmr: {
            host: "localhost",
            port: 5174,
            clientPort: 5174,
        },
        headers: {
            "X-Content-Type-Options": "nosniff",
            "X-Frame-Options": "DENY",
            "X-XSS-Protection": "1; mode=block",
            "Referrer-Policy": "strict-origin-when-cross-origin",
        },
    },

    // CSS handling with strict optimization
    css: {
        devSourcemap: IS_DEVELOPMENT,
        preprocessorOptions: {
            scss: {
                charset: false,
            },
        },
    },

    // Resolve configuration with strict settings
    resolve: {
        alias: {
            "@": "/resources/js",
            "~": "/resources",
        },
        extensions: [".js", ".ts", ".jsx", ".tsx", ".vue", ".json"],
        preserveSymlinks: false,
    },

    // Enhanced error handling and logging
    logLevel: IS_DEVELOPMENT ? "info" : "warn",
    clearScreen: false,

    // Strict mode configurations
    define: {
        __DEV__: IS_DEVELOPMENT,
        __PROD__: IS_PRODUCTION,
        "process.env.NODE_ENV": JSON.stringify(process.env.NODE_ENV),
    },

    // Enhanced dependency optimization
    optimizeDeps: {
        include: ["axios", "alpinejs", "lodash"],
        exclude: ["@vite/client", "@vite/env"],
        esbuildOptions: {
            target: "es2020",
            supported: {
                bigint: true,
            },
        },
    },

    // Experimental features for better performance
    experimental: {
        renderBuiltUrl(filename, { hostType }) {
            if (hostType === "js") {
                return { js: `"/${filename}"` };
            } else {
                return { relative: true };
            }
        },
    },
});
