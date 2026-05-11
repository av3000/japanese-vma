import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import { visualizer } from 'rollup-plugin-visualizer';
import { defineConfig } from 'vite';
import checker from 'vite-plugin-checker';

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
	const isAnalyze = mode === 'analyze';
	const isProduction = mode === 'production';

	const manualChunks = (id: string) => {
		if (!id.includes('node_modules')) {
			return;
		}

		if (
			id.includes('/react-router/') ||
			id.includes('/react-router-dom/') ||
			id.includes('/@tanstack/react-query/')
		) {
			return 'router-query';
		}

		if (
			id.includes('/react-bootstrap/') ||
			id.includes('/bootstrap/') ||
			id.includes('/@radix-ui/') ||
			id.includes('/classnames/') ||
			id.includes('/clsx/') ||
			id.includes('/class-variance-authority/') ||
			id.includes('/tailwind-merge/')
		) {
			return 'ui';
		}

		if (id.includes('/react-hook-form/') || id.includes('/@hookform/resolvers/') || id.includes('/zod/')) {
			return 'forms';
		}

		if (id.includes('/laravel-echo/') || id.includes('/pusher-js/') || id.includes('/@sentry/react/')) {
			return 'realtime-monitoring';
		}

		if (id.includes('/react/') || id.includes('/react-dom/')) {
			return 'react-core';
		}
	};

	return {
		plugins: [
			tailwindcss(),
			checker({
				typescript: true,
				eslint: {
					useFlatConfig: true,
					lintCommand: 'eslint "./src/**/*.{ts,tsx}"',
				},
			}),
			react(),
			isAnalyze &&
				visualizer({
					filename: 'build/stats.html',
					template: 'treemap',
					gzipSize: true,
					brotliSize: true,
					open: false,
				}),
		],
		resolve: {
			alias: {
				'@': path.resolve(__dirname, './src'),
				'@/routes': path.resolve(__dirname, './src/routes'),
				'@/components': path.resolve(__dirname, './src/components'),
				'@/containers': path.resolve(__dirname, './src/containers'),
				'@/providers': path.resolve(__dirname, './src/providers'),
				'@/store': path.resolve(__dirname, './src/store'),
				'@/services': path.resolve(__dirname, './src/services'),
				'@/shared': path.resolve(__dirname, './src/shared'),
				'@/assets': path.resolve(__dirname, './src/assets'),
				'@/helpers': path.resolve(__dirname, './src/helpers'),
				'@/lib': path.resolve(__dirname, '.src/lib'),
				'@/hooks': path.resolve(__dirname, './src/hooks'),
				'@/types/*': path.resolve(__dirname, './src/types'),
				'@/storybook': path.resolve(__dirname, './storybook'),
			},
		},
		build: {
			outDir: 'build',
			cssCodeSplit: true,
			sourcemap: !isProduction,
			rollupOptions: {
				output: {
					manualChunks,
				},
			},
		},
		server: {
			proxy: {
				'/api': {
					target: 'http://host.docker.internal:8080',
					changeOrigin: true,
					secure: false,
				},
			},
		},
		css: {
			modules: {
				scopeBehaviour: 'local',
				localsConvention: 'camelCase',
				generateScopedName: '[name]__[local]___[hash:base64:5]',
			},
		},
		define: {
			// Make environment variables available to your app
			'process.env.NODE_ENV': JSON.stringify(mode),
		},
	};
});
