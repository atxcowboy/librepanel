import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
	build: {
		chunkSizeWarningLimit: 1000,
	},
	base: "./",
	plugins: [
		laravel({
			input: [
				'templates/LibrePanel/assets/scss/app.scss',
				'templates/LibrePanel/assets/js/app.js',
			],
			hotFile: 'templates/LibrePanel/hot',
			buildDirectory: '../templates/LibrePanel/build',
			refresh: true,
		}),
		vue({
			template: {
				transformAssetUrls: {
					base: null,
					includeAbsolute: false,
				},
			},
		}),
	],
	resolve: {
		alias: {
			vue: 'vue/dist/vue.esm-bundler.js',
		},
	},
});
