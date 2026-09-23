import react from '@vitejs/plugin-react';
import path from 'path';
import { visualizer } from 'rollup-plugin-visualizer';
import { defineConfig } from 'vite';
import checker from 'vite-plugin-checker';

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
	const isAnalyze = mode === 'analyze';
	const isProduction = mode === 'production';

	// Docker Desktop on Windows does not deliver filesystem events through the bind mount, so
	// the container sets CHOKIDAR_USEPOLLING and the watcher has to stat the tree instead. One
	// sweep of src/ costs ~8s over that mount, and chokidar's 100 ms default asks for it
	// continuously - the poller then starves the same event loop that serves module transforms,
	// which turned a cold page load into ~200s of 5-second modules. At 3 s the poller is cheap
	// enough that cold loads match a non-polling server (11.9s vs 13.1s for 40 modules) and
	// edits are still picked up within ~3s. Outside Docker this is all skipped: native file
	// events are instant and free.
	const usePolling = process.env.CHOKIDAR_USEPOLLING === 'true';
	const pollInterval = Number(process.env.VITE_POLL_INTERVAL) || 3000;

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
			id.includes('/@radix-ui/') ||
			id.includes('/classnames/')
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
			// Supported-browser policy (STYLING-01). Matches Baseline "widely available":
			// current Chromium, Firefox and WebKit releases from the last ~30 months.
			// `browserslist` in package.json is not read by Vite; this is the source of truth.
			target: ['es2022', 'chrome107', 'edge107', 'firefox104', 'safari16'],
			cssCodeSplit: true,
			sourcemap: !isProduction,
			rollupOptions: {
				output: {
					manualChunks,
				},
			},
		},
		server: {
			watch: usePolling
				? {
						usePolling: true,
						interval: pollInterval,
						binaryInterval: pollInterval * 3,
					}
				: undefined,
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
