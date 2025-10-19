import { defineConfig } from 'vitest/config'
import path from 'node:path'
import vue from '@vitejs/plugin-vue'              

export default defineConfig({
  plugins: [vue()],                               
  test: {
    environment: 'happy-dom',
    globals: true,
    clearMocks: true,
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
})