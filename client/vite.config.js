import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
      "@components": path.resolve(__dirname, "./src/components"),
      "@containers": path.resolve(__dirname, "./src/containers"),
      "@store": path.resolve(__dirname, "./src/store"),
      "@services": path.resolve(__dirname, "./src/services"),
      "@shared": path.resolve(__dirname, "./src/shared"),
      "@assets": path.resolve(__dirname, "./src/assets"),
    },
  },
  server: {
    proxy: {
      // Proxy API requests to Docker backend
      "/api": {
        target: "http://localhost:8080", // Your Docker backend URL
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
