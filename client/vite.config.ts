import react from '@vitejs/plugin-react';
import path from 'path';
import { defineConfig } from 'vite';
import checker from 'vite-plugin-checker';

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
	const isProduction = mode === 'production';
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
				'@/hooks': path.resolve(__dirname, './src/hooks'),
				'@/types/*': path.resolve(__dirname, './src/types'),
				'@/storybook': path.resolve(__dirname, './storybook'),
			},
		},
		build: {
			outDir: 'build',
			cssCodeSplit: false,
			sourcemap: !isProduction,
			rollupOptions: {
				output: {
					manualChunks: (id) => {
						if (id.includes('node_modules')) {
							return 'vendor';
						}
					},
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
