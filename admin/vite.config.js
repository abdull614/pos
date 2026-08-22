// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    tailwindcss(),
  ],
  server: {
    host: 'localhost',   // paksa dev client pakai localhost
    port: 5173,
    strictPort: true,
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
    hmr: {
      host: 'localhost', // HMR client akan connect ke localhost:5173
    },
  },
});
