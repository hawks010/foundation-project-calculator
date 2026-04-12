import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'assets/admin',
    emptyOutDir: true,
    manifest: false,
    rollupOptions: {
      input: 'src/admin/main.jsx',
      output: {
        entryFileNames: 'admin-app.js',
        chunkFileNames: '[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'admin-app.css';
          }
          return '[name][extname]';
        },
      },
    },
  },
});
