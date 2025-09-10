import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';
import postcss from 'postcss';

// Ensure all PostCSS parses include a `from` option to silence warnings
const originalParse = postcss.parse;
postcss.parse = (css, opts: any = {}) =>
  originalParse(css, { from: opts.from || 'src/assets/main.css', ...opts });

export default defineConfig(({ command }) => ({
  plugins: [vue()],
  base: '',
  envPrefix: ['VITE_', 'API_'],
  server: {
    cors: { origin: '*' },
    watch: { usePolling: true },
    ...(command !== 'serve' ? { hmr: false } : {}),
  },
  preview: { cors: { origin: '*' } },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
      '@dc': path.resolve(__dirname, '../dashcode-full-source-code/src'),
      '@headlessui/vue': path.resolve(
        __dirname,
        'node_modules/@headlessui/vue',
      ),
    },
    extensions: ['.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json', '.vue'],
  },
  css: {
    // Use the modern Sass API to remove legacy-js-api warnings
    preprocessorOptions: {
      scss: { api: 'modern' },
      sass: { api: 'modern' },
    },
    // Provide an explicit `from` so PostCSS plugins receive a source filename
    postcss: {
      plugins: [tailwindcss(), autoprefixer()],
      options: {
        from: 'src/assets/main.css',
        // Suppress missing `from` warnings from third-party plugins
        logger: { warn: () => {}, warnOnce: () => {} },
      },
    },
  },
  build: {
    outDir: '../public/build',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) return 'vendor';
        },
      },
    },
  },
}));
