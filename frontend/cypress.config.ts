import { defineConfig } from 'cypress'
import vitePreprocessor from '@cypress/vite-dev-server'
import path from 'path'
import { translationTasks } from './cypress/plugins/translation-tasks'

export default defineConfig({
  e2e: {
    setupNodeEvents(on, config) {
      on('dev-server:start', (options) => {
        return vitePreprocessor({
          ...options,
          viteConfig: {
            configFile: path.resolve(__dirname, 'vite.config.ts')
          }
        })
      })
      
      // Add custom tasks here
      on('task', {
        log(message) {
          console.log(message)
          return null
        },
        table(message) {
          console.table(message)
          return null
        },
        // Tâches pour les tests de traduction
        ...translationTasks
      })
      
      return config
    },
    baseUrl: 'http://localhost:5173',
    viewportWidth: 1280,
    viewportHeight: 720,
    video: true,
    screenshotOnRunFailure: true,
    defaultCommandTimeout: 10000,
    requestTimeout: 10000,
    responseTimeout: 10000,
    specPattern: 'cypress/e2e/**/*.{cy,spec}.{js,jsx,ts,tsx}',
    supportFile: 'cypress/support/e2e.ts',
    fixturesFolder: 'cypress/fixtures',
    downloadsFolder: 'cypress/downloads',
    videosFolder: 'cypress/videos',
    screenshotsFolder: 'cypress/screenshots'
  },
  
  component: {
    devServer: {
      framework: 'vue',
      bundler: 'vite'
    },
    specPattern: 'src/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'cypress/support/component.ts'
  }
})