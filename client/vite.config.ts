import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import checker from "vite-plugin-checker";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  const isProduction = mode === "production";
  return {
    plugins: [
      checker({
        typescript: true,
      }),
      react(),
    ],
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "./src"),
        "@/routes": path.resolve(__dirname, "./src/routes"),
        "@/components": path.resolve(__dirname, "./src/components"),
        "@/containers": path.resolve(__dirname, "./src/containers"),
        "@/store": path.resolve(__dirname, "./src/store"),
        "@/services": path.resolve(__dirname, "./src/services"),
        "@/shared": path.resolve(__dirname, "./src/shared"),
        "@/assets": path.resolve(__dirname, "./src/assets"),
        "@/helpers": path.resolve(__dirname, "./src/helpers"),
      },
    },
    build: {
      cssCodeSplit: false,
      sourcemap: !isProduction,
      rollupOptions: {
        output: {
          manualChunks: (id) => {
            if (id.includes("node_modules")) {
              return "vendor";
            }
          },
        },
      },
    },
    server: {
      proxy: {
        "/api": {
          target: "http://localhost:8080",
          changeOrigin: true,
          secure: false,
        },
      },
    },
    css: {
      modules: {
        scopeBehaviour: "local",
        localsConvention: "camelCase",
        generateScopedName: "[name]__[local]___[hash:base64:5]",
      },
    },
    define: {
      // Make environment variables available to your app
      "process.env.NODE_ENV": JSON.stringify(mode),
    },
  };
});
