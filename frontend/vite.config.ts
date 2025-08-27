import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

// Silence deprecation warnings from Dart Sass' legacy JS API and
// ensure file changes are detected in environments where file system
// events are unreliable (e.g. Docker or network mounts).
process.env.SASS_SILENCE_DEPRECATIONS = 'legacy-js-api';

export default defineConfig(({ command }) => ({
  plugins: [vue()],
  base: '',
  envPrefix: ['VITE_', 'API_'],
  server: {
    watch: {
      // Use polling so `npm run dev` recompiles when files change
      usePolling: true,
    },
    // Disable Vite's HMR client in build/previews to avoid
    // unnecessary websocket connections in production builds.
    ...(command !== 'serve' ? { hmr: false } : {}),
  },
  css: {
    preprocessorOptions: {
      scss: {
        // Avoid Dart Sass legacy API deprecation warnings
        silenceDeprecations: ['legacy-js-api'],
      },
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
    extensions: ['.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json', '.vue'],
  },
  build: {
    outDir: '../public',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            return 'vendor';
          }
        },
      },
    },
  },
}));
