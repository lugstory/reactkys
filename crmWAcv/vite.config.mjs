import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig(({ mode }) => ({
  plugins: [react()],
  publicDir: 'static',
  resolve: {
    alias: {
      Utils: fileURLToPath(new URL('./src/utils', import.meta.url)),
    },
  },
  define: {
    PRODUCTION: JSON.stringify(mode === 'production'),
  },
  server: {
    port: 3000,
    open: false,
    host: '0.0.0.0',
  },
  build: {
    outDir: 'dist',
  },
}));
