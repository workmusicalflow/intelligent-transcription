/**
 * Commandes Cypress personnalisées pour les tests de traduction
 */

declare global {
  namespace Cypress {
    interface Chainable {
      /**
       * Créer une transcription de test
       */
      createTestTranscription(transcription: any): Chainable<string>
      
      /**
       * Créer une traduction de test
       */
      createTestTranslation(translation: any): Chainable<string>
      
      /**
       * Compléter une traduction (simulation)
       */
      completeTranslation(data: any): Chainable<void>
      
      /**
       * Réinitialiser la base de données de test
       */
      resetTranslationDatabase(): Chainable<void>
      
      /**
       * Attendre qu'une traduction soit complétée
       */
      waitForTranslationCompletion(translationId: string, timeout?: number): Chainable<void>
      
      /**
       * Vérifier le téléchargement d'un fichier
       */
      verifyDownload(filename: string): Chainable<void>
      
      /**
       * Se connecter avec un utilisateur de test
       */
      loginAsTestUser(userType?: 'basic' | 'premium' | 'admin'): Chainable<void>
      
      /**
       * Naviguer vers la page traductions
       */
      visitTranslations(): Chainable<void>
      
      /**
       * Créer une traduction via l'interface
       */
      createTranslationViaUI(options: {
        transcriptionId: string
        targetLanguage: string
        provider?: string
        config?: any
      }): Chainable<string>
      
      /**
       * Vérifier qu'une notification apparaît
       */
      checkNotification(type: 'success' | 'error', message?: string): Chainable<void>
    }
  }
}

// Implémentation des commandes

Cypress.Commands.add('createTestTranscription', (transcription) => {
  return cy.task('db:createTranscription', transcription).then((id) => {
    cy.wrap(id).as('testTranscriptionId')
    return cy.wrap(id)
  })
})

Cypress.Commands.add('createTestTranslation', (translation) => {
  return cy.task('db:createTranslation', translation).then((id) => {
    cy.wrap(id).as('testTranslationId')
    return cy.wrap(id)
  })
})

Cypress.Commands.add('completeTranslation', (data) => {
  return cy.task('translation:complete', data)
})

Cypress.Commands.add('resetTranslationDatabase', () => {
  return cy.task('db:resetTranslations')
})

Cypress.Commands.add('waitForTranslationCompletion', (translationId, timeout = 30000) => {
  const checkStatus = () => {
    return cy.request(`/api/v2/translations/status/${translationId}`)
      .then((response) => {
        if (response.body.data.status === 'completed') {
          return response.body.data
        }
        if (response.body.data.status === 'failed') {
          throw new Error('Translation failed')
        }
        // Si en cours, attendre et réessayer
        cy.wait(1000)
        return checkStatus()
      })
  }
  
  return cy.wrap(null).then(() => checkStatus())
})

Cypress.Commands.add('verifyDownload', (filename) => {
  const downloadsFolder = Cypress.config('downloadsFolder')
  return cy.readFile(`${downloadsFolder}/${filename}`, { timeout: 10000 })
})

Cypress.Commands.add('loginAsTestUser', (userType = 'basic') => {
  const users = {
    basic: {
      email: 'test.basic@example.com',
      password: 'TestPassword123!'
    },
    premium: {
      email: 'test.premium@example.com', 
      password: 'TestPassword123!'
    },
    admin: {
      email: 'test.admin@example.com',
      password: 'AdminPassword123!'
    }
  }
  
  const user = users[userType]
  
  // Créer l'utilisateur s'il n'existe pas
  cy.task('db:createUser', { ...user, type: userType })
  
  // Se connecter
  cy.visit('/login')
  cy.get('[data-cy="email-input"]').type(user.email)
  cy.get('[data-cy="password-input"]').type(user.password)
  cy.get('[data-cy="login-button"]').click()
  
  // Vérifier la connexion
  cy.url().should('include', '/dashboard')
  cy.get('[data-cy="user-menu"]').should('be.visible')
  
  return cy.wrap(user)
})

Cypress.Commands.add('visitTranslations', () => {
  cy.visit('/translations')
  cy.get('[data-cy="translations-page"]').should('be.visible')
  cy.get('h1').should('contain', 'Traductions')
})

Cypress.Commands.add('createTranslationViaUI', (options) => {
  const {
    transcriptionId,
    targetLanguage,
    provider = 'gpt-4o-mini',
    config = {}
  } = options
  
  // Naviguer vers la création
  cy.visitTranslations()
  cy.get('[data-cy="create-translation-button"]').click()
  cy.get('[data-cy="translation-creator"]').should('be.visible')
  
  // Remplir le formulaire
  cy.get('[data-cy="transcription-select"]').select(transcriptionId)
  cy.get(`[data-cy="target-language-${targetLanguage}"]`).click()
  
  if (provider !== 'gpt-4o-mini') {
    cy.get(`[data-cy="provider-${provider}"]`).click()
  }
  
  // Configuration avancée si fournie
  if (config.optimize_for_dubbing) {
    cy.get('[data-cy="optimize-for-dubbing"]').check()
  }
  if (config.preserve_emotions) {
    cy.get('[data-cy="preserve-emotions"]').check()
  }
  if (config.style_adaptation) {
    cy.get('[data-cy="style-adaptation-select"]').select(config.style_adaptation)
  }
  if (config.quality_threshold) {
    cy.get('[data-cy="quality-threshold-select"]').select(config.quality_threshold.toString())
  }
  
  // Créer la traduction
  cy.get('[data-cy="create-translation-button"]').click()
  
  // Récupérer l'ID de la traduction créée depuis la notification ou l'URL
  return cy.get('[data-cy="success-notification"]').should('be.visible')
    .then(() => {
      // Extraire l'ID depuis la notification ou faire une requête pour récupérer la dernière traduction
      return cy.request('/api/v2/translations/list?limit=1').then((response) => {
        const latestTranslation = response.body.data.translations[0]
        return cy.wrap(latestTranslation.id)
      })
    })
})

Cypress.Commands.add('checkNotification', (type, message) => {
  const selector = type === 'success' ? '[data-cy="success-notification"]' : '[data-cy="error-notification"]'
  
  cy.get(selector).should('be.visible')
  
  if (message) {
    cy.get(selector).should('contain', message)
  }
  
  // Vérifier que la notification disparaît après un délai
  cy.get(selector, { timeout: 6000 }).should('not.exist')
})

// Export pour TypeScript
export {}