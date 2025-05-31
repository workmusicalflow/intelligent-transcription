describe('Authentication', () => {
  beforeEach(() => {
    cy.clearAppData()
    cy.visit('/')
  })

  describe('Login', () => {
    it('should display login form', () => {
      cy.visit('/login')
      
      cy.get('h1').should('contain', 'Connexion')
      cy.get('input[type="email"]').should('be.visible')
      cy.get('input[type="password"]').should('be.visible')
      cy.get('button[type="submit"]').should('contain', 'Se connecter')
    })

    it('should show validation errors for empty fields', () => {
      cy.visit('/login')
      
      cy.get('button[type="submit"]').click()
      
      cy.get('.error-message').should('contain', 'Email requis')
      cy.get('.error-message').should('contain', 'Mot de passe requis')
    })

    it('should show error for invalid credentials', () => {
      cy.visit('/login')
      
      cy.get('input[type="email"]').type('wrong@example.com')
      cy.get('input[type="password"]').type('wrongpassword')
      cy.get('button[type="submit"]').click()
      
      cy.wait('@login')
      cy.get('.notification-error').should('contain', 'Identifiants invalides')
    })

    it('should login successfully with valid credentials', () => {
      cy.visit('/login')
      
      cy.get('input[type="email"]').type('user@example.com')
      cy.get('input[type="password"]').type('password123')
      cy.get('button[type="submit"]').click()
      
      cy.wait('@login')
      cy.url().should('include', '/dashboard')
      cy.get('.user-menu').should('contain', 'user@example.com')
    })

    it('should redirect to requested page after login', () => {
      // Try to visit protected page
      cy.visit('/transcriptions')
      
      // Should redirect to login
      cy.url().should('include', '/login')
      cy.url().should('include', 'redirect=%2Ftranscriptions')
      
      // Login
      cy.get('input[type="email"]').type('user@example.com')
      cy.get('input[type="password"]').type('password123')
      cy.get('button[type="submit"]').click()
      
      // Should redirect to originally requested page
      cy.wait('@login')
      cy.url().should('include', '/transcriptions')
    })

    it('should persist login across page refreshes', () => {
      cy.login('user@example.com', 'password123')
      cy.visit('/dashboard')
      
      // Refresh page
      cy.reload()
      
      // Should still be logged in
      cy.url().should('include', '/dashboard')
      cy.get('.user-menu').should('exist')
    })
  })

  describe('Logout', () => {
    beforeEach(() => {
      cy.login('user@example.com', 'password123')
    })

    it('should logout successfully', () => {
      cy.visit('/dashboard')
      
      cy.get('.user-menu').click()
      cy.get('[data-test="logout-button"]').click()
      
      cy.wait('@logout')
      cy.url().should('include', '/login')
      
      // Try to visit protected page
      cy.visit('/dashboard')
      cy.url().should('include', '/login')
    })

    it('should clear all user data on logout', () => {
      cy.visit('/dashboard')
      
      // Store some data
      cy.window().then(win => {
        win.localStorage.setItem('user-preference', 'dark-mode')
      })
      
      // Logout
      cy.get('.user-menu').click()
      cy.get('[data-test="logout-button"]').click()
      
      // Check data is cleared
      cy.window().its('localStorage.auth-token').should('not.exist')
      cy.window().its('localStorage.user-preference').should('not.exist')
    })
  })

  describe('Registration', () => {
    it('should display registration form', () => {
      cy.visit('/register')
      
      cy.get('h1').should('contain', 'Créer un compte')
      cy.get('input[name="name"]').should('be.visible')
      cy.get('input[type="email"]').should('be.visible')
      cy.get('input[name="password"]').should('be.visible')
      cy.get('input[name="confirmPassword"]').should('be.visible')
    })

    it('should validate password confirmation', () => {
      cy.visit('/register')
      
      cy.get('input[name="name"]').type('Test User')
      cy.get('input[type="email"]').type('test@example.com')
      cy.get('input[name="password"]').type('password123')
      cy.get('input[name="confirmPassword"]').type('password456')
      cy.get('button[type="submit"]').click()
      
      cy.get('.error-message').should('contain', 'Les mots de passe ne correspondent pas')
    })

    it('should register new user successfully', () => {
      cy.visit('/register')
      
      cy.get('input[name="name"]').type('New User')
      cy.get('input[type="email"]').type('newuser@example.com')
      cy.get('input[name="password"]').type('password123')
      cy.get('input[name="confirmPassword"]').type('password123')
      cy.get('button[type="submit"]').click()
      
      cy.wait('@register')
      cy.url().should('include', '/dashboard')
      cy.get('.notification-success').should('contain', 'Compte créé avec succès')
    })
  })

  describe('Password Reset', () => {
    it('should send password reset email', () => {
      cy.visit('/login')
      cy.get('a').contains('Mot de passe oublié').click()
      
      cy.url().should('include', '/password-reset')
      cy.get('input[type="email"]').type('user@example.com')
      cy.get('button[type="submit"]').click()
      
      cy.wait('@passwordReset')
      cy.get('.notification-success').should('contain', 'Email de réinitialisation envoyé')
    })

    it('should reset password with valid token', () => {
      cy.visit('/password-reset?token=valid-reset-token')
      
      cy.get('input[name="password"]').type('newpassword123')
      cy.get('input[name="confirmPassword"]').type('newpassword123')
      cy.get('button[type="submit"]').click()
      
      cy.wait('@resetPassword')
      cy.url().should('include', '/login')
      cy.get('.notification-success').should('contain', 'Mot de passe réinitialisé')
    })
  })

  describe('Protected Routes', () => {
    it('should redirect to login when accessing protected routes', () => {
      const protectedRoutes = [
        '/dashboard',
        '/transcriptions',
        '/chat',
        '/settings',
        '/profile'
      ]
      
      protectedRoutes.forEach(route => {
        cy.visit(route)
        cy.url().should('include', '/login')
      })
    })

    it('should allow access to protected routes when authenticated', () => {
      cy.login('user@example.com', 'password123')
      
      const protectedRoutes = [
        '/dashboard',
        '/transcriptions',
        '/chat',
        '/settings',
        '/profile'
      ]
      
      protectedRoutes.forEach(route => {
        cy.visit(route)
        cy.url().should('include', route)
      })
    })
  })
})