// ***********************************************************
// This file is processed and loaded automatically before test files.
// You can change the location of this file or turn off processing
// by changing the supportFile configuration option.
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Custom types
declare global {
  namespace Cypress {
    interface Chainable {
      /**
       * Custom command to login
       * @example cy.login('user@example.com', 'password')
       */
      login(email: string, password: string): Chainable<void>
      
      /**
       * Custom command to logout
       * @example cy.logout()
       */
      logout(): Chainable<void>
      
      /**
       * Custom command to seed the database
       * @example cy.seed('users')
       */
      seed(fixture: string): Chainable<void>
      
      /**
       * Custom command to clear all app data
       * @example cy.clearAppData()
       */
      clearAppData(): Chainable<void>
      
      /**
       * Custom command to wait for API
       * @example cy.waitForApi()
       */
      waitForApi(): Chainable<void>
      
      /**
       * Custom command to upload file
       * @example cy.uploadFile('audio.mp3', 'audio/mp3')
       */
      uploadFile(fileName: string, mimeType: string): Chainable<void>
    }
  }
}

// Prevent TypeScript from reading file as legacy script
export {}