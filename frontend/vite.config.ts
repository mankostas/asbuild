import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import sass from 'sass-embedded';

export default defineConfig(({ command }) => ({
  plugins: [vue()],
  base: '',
  envPrefix: ['VITE_', 'API_'],
  server: {
    cors: { origin: '*' },
    watch: {
      // Use polling so `npm run dev` recompiles when files change
      usePolling: true,
    },
    // Disable Vite's HMR client in build/previews to avoid
    // unnecessary websocket connections in production builds.
    ...(command !== 'serve' ? { hmr: false } : {}),
  },
  preview: {
    cors: { origin: '*' },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
    extensions: ['.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json', '.vue'],
  },
  css: {
    preprocessorOptions: {
      scss: { implementation: sass, api: 'modern' },
      sass: { implementation: sass, api: 'modern' },
    },
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
