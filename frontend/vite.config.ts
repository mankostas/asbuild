import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

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
    alias: { '@': path.resolve(__dirname, 'src') },
    extensions: ['.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json', '.vue'],
  },
  css: {
    // Use Dart Sass modern embedded compiler to get rid of legacy-js-api warnings
    preprocessorOptions: {
      scss: { api: 'modern-compiler' },
      sass: { api: 'modern-compiler' },
    },
    // Provide an explicit 'from' so PostCSS plugins see a source filename
    postcss: {
      plugins: [tailwindcss(), autoprefixer()],
      from: 'src/assets/main.css'
    }
  },
  build: {
    outDir: '../public',
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
