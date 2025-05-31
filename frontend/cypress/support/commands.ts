// Custom Cypress commands

// Login command
Cypress.Commands.add('login', (email: string, password: string) => {
  cy.session([email, password], () => {
    cy.visit('/login')
    
    cy.get('input[type="email"]').type(email)
    cy.get('input[type="password"]').type(password)
    cy.get('button[type="submit"]').click()
    
    // Wait for redirect to dashboard
    cy.url().should('include', '/dashboard')
    
    // Ensure auth token is stored
    cy.window().its('localStorage.auth-token').should('exist')
  })
})

// Logout command
Cypress.Commands.add('logout', () => {
  cy.window().then(win => {
    win.localStorage.removeItem('auth-token')
  })
  cy.visit('/login')
})

// Seed database with fixture data
Cypress.Commands.add('seed', (fixture: string) => {
  cy.fixture(fixture).then(data => {
    cy.request('POST', '/api/test/seed', data)
  })
})

// Clear all app data
Cypress.Commands.add('clearAppData', () => {
  cy.window().then(win => {
    win.localStorage.clear()
    win.sessionStorage.clear()
  })
  
  // Clear cookies
  cy.clearCookies()
  
  // Clear IndexedDB if used
  cy.window().then(win => {
    if (win.indexedDB) {
      win.indexedDB.databases().then(databases => {
        databases.forEach(db => {
          if (db.name) {
            win.indexedDB.deleteDatabase(db.name)
          }
        })
      })
    }
  })
})

// Wait for API to be ready
Cypress.Commands.add('waitForApi', () => {
  cy.request({
    url: '/api/health',
    retryOnStatusCodeFailure: true,
    retryOnNetworkFailure: true,
    timeout: 30000
  }).its('status').should('eq', 200)
})

// Upload file command
Cypress.Commands.add('uploadFile', (fileName: string, mimeType: string) => {
  cy.fixture(fileName, 'base64').then(fileContent => {
    cy.get('input[type="file"]').then(input => {
      const blob = Cypress.Blob.base64StringToBlob(fileContent, mimeType)
      const file = new File([blob], fileName, { type: mimeType })
      const dataTransfer = new DataTransfer()
      dataTransfer.items.add(file)
      
      const inputElement = input[0] as HTMLInputElement
      inputElement.files = dataTransfer.files
      
      cy.wrap(input).trigger('change', { force: true })
    })
  })
})

// Intercept common API calls
beforeEach(() => {
  // Auth endpoints
  cy.intercept('POST', '/api/auth/login').as('login')
  cy.intercept('POST', '/api/auth/logout').as('logout')
  cy.intercept('GET', '/api/auth/me').as('getUser')
  
  // Transcription endpoints
  cy.intercept('GET', '/api/transcriptions').as('getTranscriptions')
  cy.intercept('POST', '/api/transcriptions').as('createTranscription')
  cy.intercept('GET', '/api/transcriptions/*').as('getTranscription')
  
  // Chat endpoints
  cy.intercept('GET', '/api/conversations').as('getConversations')
  cy.intercept('POST', '/api/conversations').as('createConversation')
  cy.intercept('POST', '/api/conversations/*/messages').as('sendMessage')
})

export {}