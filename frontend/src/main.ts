import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import { apolloClient } from './api/apollo'
import { DefaultApolloClient } from '@vue/apollo-composable'
import { setupSentry, captureException } from './plugins/sentry'
import performancePlugin from './plugins/performance'
import { accessibilityDirectives } from './composables/useAccessibility'

import App from './App.vue'
import './style.css'

// Create Vue app
const app = createApp(App)

// Create Pinia instance
const pinia = createPinia()

// Use plugins
app.use(pinia)
app.use(router)
app.use(performancePlugin)

// Register accessibility directives
Object.keys(accessibilityDirectives).forEach(name => {
  app.directive(name, accessibilityDirectives[name as keyof typeof accessibilityDirectives])
})

// Setup Sentry error tracking
setupSentry(app, router)

// Provide Apollo client
app.provide(DefaultApolloClient, apolloClient)

// Global error handler
app.config.errorHandler = (err, instance, info) => {
  console.error('Global error:', err)
  console.error('Vue instance:', instance)
  console.error('Error info:', info)
  
  // Send to Sentry
  if (err instanceof Error) {
    captureException(err, {
      component: instance?.$options.name || 'Unknown',
      info
    })
  }
}

// Global performance monitoring
if (import.meta.env.PROD) {
  app.config.performance = true
}

// Mount app
app.mount('#app')