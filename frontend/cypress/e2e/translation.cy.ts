/**
 * Tests E2E pour le workflow complet de traduction
 * Validée le pipeline: transcription → traduction → téléchargement
 */

describe('Workflow Translation Complet', () => {
  const testUser = {
    email: 'translation.test@example.com',
    password: 'TestTranslation123!'
  }

  const testTranscription = {
    id: 'test_translation_workflow_' + Date.now(),
    title: 'Test Translation Workflow',
    language: 'en',
    segments: [
      {
        id: 1,
        start: 0.0,
        end: 3.5,
        text: "Hello, this is Matt. This MCP tutorial is all you'll need.",
        words: [
          { word: "Hello", start: 0.0, end: 0.5 },
          { word: "this", start: 0.6, end: 0.8 },
          { word: "is", start: 0.9, end: 1.0 },
          { word: "Matt", start: 1.1, end: 1.4 },
          { word: "This", start: 2.0, end: 2.3 },
          { word: "MCP", start: 2.4, end: 2.7 },
          { word: "tutorial", start: 2.8, end: 3.1 },
          { word: "is", start: 3.2, end: 3.3 },
          { word: "all", start: 3.4, end: 3.5 }
        ]
      }
    ]
  }

  beforeEach(() => {
    // Configurer l'environnement de test
    cy.task('resetDatabase')
    cy.task('seedTestUser', testUser)
    cy.task('seedTestTranscription', testTranscription)
    
    // Se connecter
    cy.visit('/login')
    cy.get('[data-cy="email-input"]').type(testUser.email)
    cy.get('[data-cy="password-input"]').type(testUser.password)
    cy.get('[data-cy="login-button"]').click()
    
    // Vérifier que l'utilisateur est connecté
    cy.url().should('include', '/dashboard')
    cy.get('[data-cy="user-menu"]').should('be.visible')
  })

  describe('Navigation vers les traductions', () => {
    it('devrait permettre d\'accéder à la page traductions depuis la sidebar', () => {
      // Cliquer sur le lien traductions dans la sidebar
      cy.get('[data-cy="sidebar-translations"]').should('be.visible').click()
      
      // Vérifier l'URL et le contenu
      cy.url().should('include', '/translations')
      cy.get('[data-cy="translations-page"]').should('be.visible')
      cy.get('h1').should('contain', 'Traductions')
      
      // Vérifier le badge "NEW"
      cy.get('[data-cy="sidebar-translations"]').should('contain', 'NEW')
    })

    it('devrait afficher les statistiques rapides', () => {
      cy.visit('/translations')
      
      // Attendre le chargement des stats
      cy.get('[data-cy="quick-stats"]').should('be.visible')
      cy.get('[data-cy="stat-total-translations"]').should('be.visible')
      cy.get('[data-cy="stat-success-rate"]').should('be.visible')
      cy.get('[data-cy="stat-total-cost"]').should('be.visible')
      cy.get('[data-cy="stat-average-quality"]').should('be.visible')
    })
  })

  describe('Création d\'une traduction', () => {
    beforeEach(() => {
      cy.visit('/translations')
      cy.get('[data-cy="create-translation-button"]').click()
      cy.get('[data-cy="translation-creator"]').should('be.visible')
    })

    it('devrait permettre de créer une traduction avec configuration basique', () => {
      // Sélectionner la transcription
      cy.get('[data-cy="transcription-select"]').select(testTranscription.id)
      
      // Sélectionner la langue française
      cy.get('[data-cy="target-language-fr"]').click()
      cy.get('[data-cy="target-language-fr"]').should('have.class', 'border-blue-500')
      
      // Vérifier que le provider recommandé est sélectionné
      cy.get('[data-cy="provider-gpt-4o-mini"]').should('have.class', 'border-blue-500')
      
      // Estimer le coût
      cy.get('[data-cy="estimate-cost-button"]').click()
      cy.get('[data-cy="cost-estimate"]').should('be.visible')
      cy.get('[data-cy="estimated-cost"]').should('contain', '$')
      cy.get('[data-cy="estimated-time"]').should('contain', 's')
      
      // Créer la traduction
      cy.get('[data-cy="create-translation-button"]').click()
      
      // Vérifier la notification de succès
      cy.get('[data-cy="success-notification"]').should('be.visible')
      cy.get('[data-cy="success-notification"]').should('contain', 'créée avec succès')
      
      // Vérifier la redirection vers la liste
      cy.get('[data-cy="translation-list"]').should('be.visible')
    })

    it('devrait permettre de configurer les options avancées', () => {
      // Configuration avancée
      cy.get('[data-cy="transcription-select"]').select(testTranscription.id)
      cy.get('[data-cy="target-language-fr"]').click()
      
      // Modifier le style d'adaptation
      cy.get('[data-cy="style-adaptation-select"]').select('cinematic')
      
      // Modifier le seuil de qualité
      cy.get('[data-cy="quality-threshold-select"]').select('0.95')
      
      // Activer les options spécialisées
      cy.get('[data-cy="optimize-for-dubbing"]').check()
      cy.get('[data-cy="preserve-emotions"]').check()
      cy.get('[data-cy="use-character-names"]').check()
      cy.get('[data-cy="technical-terms-handling"]').check()
      cy.get('[data-cy="length-optimization"]').check()
      
      // Vérifier que les options sont cochées
      cy.get('[data-cy="optimize-for-dubbing"]').should('be.checked')
      cy.get('[data-cy="preserve-emotions"]').should('be.checked')
      
      // Créer avec configuration avancée
      cy.get('[data-cy="create-translation-button"]').click()
      cy.get('[data-cy="success-notification"]').should('be.visible')
    })

    it('devrait valider les champs requis', () => {
      // Essayer de créer sans sélectionner de transcription
      cy.get('[data-cy="create-translation-button"]').should('be.disabled')
      
      // Sélectionner transcription mais pas de langue
      cy.get('[data-cy="transcription-select"]').select(testTranscription.id)
      cy.get('[data-cy="create-translation-button"]').should('be.disabled')
      
      // Sélectionner langue - le bouton devrait être activé
      cy.get('[data-cy="target-language-fr"]').click()
      cy.get('[data-cy="create-translation-button"]').should('not.be.disabled')
    })
  })

  describe('Gestion des traductions existantes', () => {
    beforeEach(() => {
      // Créer une traduction de test
      cy.task('createTestTranslation', {
        transcription_id: testTranscription.id,
        target_language: 'fr',
        provider_used: 'gpt-4o-mini',
        status: 'completed',
        quality_score: 0.92,
        segments_count: 1,
        total_duration: 3.5
      }).then((translationId) => {
        cy.wrap(translationId).as('testTranslationId')
      })
      
      cy.visit('/translations')
    })

    it('devrait afficher la liste des traductions avec filtres', () => {
      // Vérifier que la liste est visible
      cy.get('[data-cy="translation-list"]').should('be.visible')
      cy.get('[data-cy="translation-card"]').should('have.length.at.least', 1)
      
      // Tester les filtres
      cy.get('[data-cy="filter-language"]').select('fr')
      cy.get('[data-cy="translation-card"]').should('be.visible')
      
      cy.get('[data-cy="filter-provider"]').select('gpt-4o-mini')
      cy.get('[data-cy="translation-card"]').should('be.visible')
      
      cy.get('[data-cy="filter-status"]').select('completed')
      cy.get('[data-cy="translation-card"]').should('be.visible')
      
      // Test recherche
      cy.get('[data-cy="search-input"]').type(testTranscription.id)
      cy.get('[data-cy="translation-card"]').should('be.visible')
    })

    it('devrait permettre de télécharger une traduction complétée', function() {
      // Trouver la traduction complétée
      cy.get('[data-cy="translation-card"]').first().within(() => {
        cy.get('[data-cy="translation-status"]').should('contain', 'Terminé')
        
        // Ouvrir le menu de téléchargement
        cy.get('[data-cy="download-button"]').click()
      })
      
      // Vérifier que le menu de téléchargement est ouvert
      cy.get('[data-cy="download-menu"]').should('be.visible')
      
      // Tester différents formats
      const formats = ['json', 'srt', 'vtt', 'txt', 'dubbing_json']
      
      formats.forEach(format => {
        cy.get('[data-cy="download-menu"]').within(() => {
          cy.get(`[data-cy="download-${format}"]`).should('be.visible')
        })
      })
      
      // Télécharger en format JSON
      cy.get(`[data-cy="download-json"]`).click()
      
      // Vérifier que le téléchargement a commencé
      // Note: En environnement de test, on peut seulement vérifier que la requête est faite
      cy.wait(1000) // Attendre le téléchargement
    })

    it('devrait afficher les détails de qualité et métriques', function() {
      cy.get('[data-cy="translation-card"]').first().within(() => {
        // Vérifier les métriques affichées
        cy.get('[data-cy="quality-score"]').should('be.visible')
        cy.get('[data-cy="quality-score"]').should('contain', '92.0%')
        
        cy.get('[data-cy="processing-cost"]').should('be.visible')
        cy.get('[data-cy="processing-time"]').should('be.visible')
        
        // Vérifier les capacités spéciales
        cy.get('[data-cy="capabilities"]').should('be.visible')
      })
    })

    it('devrait permettre de trier et paginer les résultats', () => {
      // Test tri
      cy.get('[data-cy="sort-by-select"]').select('quality_score')
      cy.get('[data-cy="sort-order-button"]').click()
      
      // Vérifier que l'ordre a changé
      cy.get('[data-cy="sort-order-button"]').should('contain', 'Asc')
      
      // Test pagination (si applicable)
      cy.get('[data-cy="pagination"]').then(($pagination) => {
        if ($pagination.is(':visible')) {
          cy.get('[data-cy="next-page-button"]').should('be.visible')
          cy.get('[data-cy="previous-page-button"]').should('be.visible')
        }
      })
    })
  })

  describe('Capacités des services de traduction', () => {
    it('devrait afficher les capacités disponibles', () => {
      cy.visit('/translations')
      
      // Ouvrir le modal des capacités
      cy.get('[data-cy="capabilities-button"]').click()
      cy.get('[data-cy="capabilities-modal"]').should('be.visible')
      
      // Vérifier les sections principales
      cy.get('[data-cy="supported-languages"]').should('be.visible')
      cy.get('[data-cy="translation-services"]').should('be.visible')
      cy.get('[data-cy="technical-capabilities"]').should('be.visible')
      
      // Vérifier les langues supportées
      cy.get('[data-cy="language-fr"]').should('be.visible')
      cy.get('[data-cy="language-fr"]').should('contain', 'Français')
      cy.get('[data-cy="language-fr"]').should('contain', 'Excellent')
      
      // Vérifier les services
      cy.get('[data-cy="service-gpt-4o-mini"]').should('be.visible')
      cy.get('[data-cy="service-hybrid"]').should('be.visible')
      cy.get('[data-cy="service-whisper-1"]').should('be.visible')
      
      // Vérifier les capacités techniques
      cy.get('[data-cy="timestamp-preservation"]').should('be.visible')
      cy.get('[data-cy="intelligent-adaptation"]').should('be.visible')
      
      // Fermer le modal
      cy.get('[data-cy="close-capabilities"]').click()
      cy.get('[data-cy="capabilities-modal"]').should('not.exist')
    })
  })

  describe('Workflow complet', () => {
    it('devrait permettre le workflow complet: transcription → traduction → téléchargement', () => {
      // 1. Naviguer vers les traductions
      cy.visit('/translations')
      cy.get('[data-cy="create-translation-button"]').click()
      
      // 2. Créer une traduction
      cy.get('[data-cy="transcription-select"]').select(testTranscription.id)
      cy.get('[data-cy="target-language-es"]').click() // Espagnol cette fois
      
      // Configurer pour le doublage
      cy.get('[data-cy="optimize-for-dubbing"]').check()
      cy.get('[data-cy="preserve-emotions"]').check()
      cy.get('[data-cy="length-optimization"]').check()
      
      // Estimer et créer
      cy.get('[data-cy="estimate-cost-button"]').click()
      cy.get('[data-cy="cost-estimate"]').should('be.visible')
      
      cy.get('[data-cy="create-translation-button"]').click()
      cy.get('[data-cy="success-notification"]').should('be.visible')
      
      // 3. Vérifier que la traduction apparaît dans la liste
      cy.get('[data-cy="translation-list"]').should('be.visible')
      cy.get('[data-cy="translation-card"]').first().within(() => {
        cy.get('[data-cy="target-language"]').should('contain', 'Español')
        cy.get('[data-cy="provider"]').should('contain', 'gpt-4o-mini')
      })
      
      // 4. Simuler la completion de la traduction (via tâche de test)
      cy.get('[data-cy="translation-card"]').first().invoke('attr', 'data-translation-id').then((translationId) => {
        cy.task('completeTranslation', {
          id: translationId,
          quality_score: 0.89,
          segments: [
            {
              id: 1,
              start: 0.0,
              end: 3.8,
              text: "Hola, soy Matt. Este tutorial MCP es todo lo que necesitarás.",
              words: [
                { word: "Hola", start: 0.0, end: 0.5 },
                { word: "soy", start: 0.6, end: 0.8 },
                { word: "Matt", start: 0.9, end: 1.2 }
              ]
            }
          ]
        })
      })
      
      // 5. Rafraîchir et vérifier la completion
      cy.reload()
      cy.get('[data-cy="translation-card"]').first().within(() => {
        cy.get('[data-cy="translation-status"]').should('contain', 'Terminé')
        cy.get('[data-cy="quality-score"]').should('contain', '89.0%')
        
        // 6. Télécharger le résultat
        cy.get('[data-cy="download-button"]').click()
      })
      
      cy.get('[data-cy="download-menu"]').should('be.visible')
      cy.get('[data-cy="download-dubbing_json"]').click()
      
      // 7. Vérifier les statistiques mises à jour
      cy.get('[data-cy="quick-stats"]').within(() => {
        cy.get('[data-cy="stat-total-translations"]').should('not.contain', '0')
        cy.get('[data-cy="stat-success-rate"]').should('be.visible')
      })
    })
  })

  describe('Gestion d\'erreurs', () => {
    it('devrait gérer les erreurs de création de traduction', () => {
      // Simuler une erreur serveur
      cy.intercept('POST', '/api/v2/translations/create', {
        statusCode: 500,
        body: { error: 'Erreur serveur simulée' }
      }).as('createTranslationError')
      
      cy.visit('/translations')
      cy.get('[data-cy="create-translation-button"]').click()
      
      cy.get('[data-cy="transcription-select"]').select(testTranscription.id)
      cy.get('[data-cy="target-language-fr"]').click()
      cy.get('[data-cy="create-translation-button"]').click()
      
      cy.wait('@createTranslationError')
      
      // Vérifier l'affichage de l'erreur
      cy.get('[data-cy="error-notification"]').should('be.visible')
      cy.get('[data-cy="error-notification"]').should('contain', 'Erreur serveur simulée')
    })

    it('devrait gérer les erreurs de chargement des capacités', () => {
      // Simuler une erreur de chargement des capacités
      cy.intercept('GET', '/api/v2/translations/capabilities', {
        statusCode: 503,
        body: { error: 'Service indisponible' }
      }).as('capabilitiesError')
      
      cy.visit('/translations')
      cy.get('[data-cy="capabilities-button"]').click()
      
      cy.wait('@capabilitiesError')
      
      // Vérifier que l'erreur est gérée gracieusement
      cy.get('[data-cy="capabilities-modal"]').should('be.visible')
      cy.get('[data-cy="capabilities-loading"]').should('be.visible')
    })
  })

  describe('Responsive et accessibilité', () => {
    it('devrait être utilisable sur mobile', () => {
      cy.viewport('iphone-6')
      cy.visit('/translations')
      
      // Vérifier que l'interface s'adapte
      cy.get('[data-cy="translations-page"]').should('be.visible')
      cy.get('[data-cy="quick-stats"]').should('be.visible')
      
      // Navigation mobile
      cy.get('[data-cy="create-translation-button"]').should('be.visible')
      cy.get('[data-cy="create-translation-button"]').click()
      
      cy.get('[data-cy="translation-creator"]').should('be.visible')
    })

    it('devrait respecter les standards d\'accessibilité', () => {
      cy.visit('/translations')
      
      // Vérifier les labels ARIA
      cy.get('[data-cy="transcription-select"]').should('have.attr', 'aria-label')
      cy.get('[data-cy="create-translation-button"]').should('have.attr', 'aria-label')
      
      // Vérifier la navigation au clavier
      cy.get('[data-cy="create-translation-button"]').focus()
      cy.focused().should('have.attr', 'data-cy', 'create-translation-button')
      
      // Vérifier le contraste (test basique)
      cy.get('h1').should('have.css', 'color').and('not.equal', 'rgba(0, 0, 0, 0)')
    })
  })
})