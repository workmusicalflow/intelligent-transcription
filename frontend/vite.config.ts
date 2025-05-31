import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
// import { pwaConfig } from './vite-pwa.config' // Désactivé temporairement

// Plugin pour l'analyse des bundles
import { visualizer } from 'rollup-plugin-visualizer'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    // Analyseur de bundle (seulement en build)
    process.env.ANALYZE && visualizer({
      filename: 'dist/stats.html',
      open: true,
      gzipSize: true,
      brotliSize: true
    })
  ].filter(Boolean), // PWA désactivé temporairement
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
    port: 5173,
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
    // Optimisations pour la production
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true
      }
    },
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia'],
          apollo: ['@apollo/client', '@vue/apollo-composable'],
          charts: ['chart.js', 'vue-chartjs'],
          utils: ['axios', 'date-fns', 'lodash-es'],
          ui: ['@headlessui/vue', '@heroicons/vue']
        },
        // Optimiser les noms de fichiers pour le cache
        chunkFileNames: 'assets/[name]-[hash].js',
        entryFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]'
      }
    },
    // Optimiser la taille des chunks
    chunkSizeWarningLimit: 1000
  },
  define: {
    __APP_VERSION__: JSON.stringify(process.env.npm_package_version),
    __API_BASE_URL__: JSON.stringify(process.env.VITE_API_BASE_URL || '/api/v2'),
    __GRAPHQL_ENDPOINT__: JSON.stringify(process.env.VITE_GRAPHQL_ENDPOINT || '/graphql')
  }
})