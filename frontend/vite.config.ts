import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
import { pwaConfig } from './vite-pwa.config'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue(), pwaConfig],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
      '@components': resolve(__dirname, 'src/components'),
      '@views': resolve(__dirname, 'src/views'),
      '@stores': resolve(__dirname, 'src/stores'),
      '@types': resolve(__dirname, 'src/types'),
      '@utils': resolve(__dirname, 'src/utils'),
      '@api': resolve(__dirname, 'src/api'),
      '@composables': resolve(__dirname, 'src/composables')
    }
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      },
      '/graphql': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },
  build: {
    outDir: '../public/dist',
    emptyOutDir: true,
    sourcemap: true,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia'],
          apollo: ['@apollo/client', '@vue/apollo-composable'],
          charts: ['chart.js', 'vue-chartjs'],
          utils: ['axios', 'date-fns', 'lodash-es']
        }
      }
    }
  },
  define: {
    __APP_VERSION__: JSON.stringify(process.env.npm_package_version),
    __API_BASE_URL__: JSON.stringify(process.env.VITE_API_BASE_URL || '/api/v2'),
    __GRAPHQL_ENDPOINT__: JSON.stringify(process.env.VITE_GRAPHQL_ENDPOINT || '/graphql')
  }
})