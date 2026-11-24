import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    host: true,
    proxy: {
      '/api': {
        target: 'http://nginx:80',
        changeOrigin: true,
      },
      '/reverb': {
        target: 'http://reverb:8080',
        changeOrigin: true,
        ws: true,
      },
      '/socket.io': {
        target: 'http://reverb:8080',
        changeOrigin: true,
        ws: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: true,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia', 'axios'],
          ui: ['@headlessui/vue', '@heroicons/vue'],
        },
      },
    },
  },
})