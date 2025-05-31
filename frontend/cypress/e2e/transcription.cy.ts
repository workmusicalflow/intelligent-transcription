describe('Transcription', () => {
  beforeEach(() => {
    cy.clearAppData()
    cy.login('user@example.com', 'password123')
  })

  describe('Upload and Transcribe', () => {
    it('should display upload form', () => {
      cy.visit('/transcriptions/new')
      
      cy.get('h1').should('contain', 'Nouvelle Transcription')
      cy.get('input[type="file"]').should('exist')
      cy.get('input[name="youtubeUrl"]').should('exist')
      cy.get('select[name="language"]').should('exist')
    })

    it('should upload audio file and start transcription', () => {
      cy.visit('/transcriptions/new')
      
      // Upload file
      cy.uploadFile('sample-audio.mp3', 'audio/mp3')
      
      // Select language
      cy.get('select[name="language"]').select('fr')
      
      // Submit
      cy.get('button[type="submit"]').click()
      
      cy.wait('@createTranscription')
      
      // Should redirect to transcription details
      cy.url().should('match', /\/transcriptions\/[\w-]+/)
      
      // Should show processing status
      cy.get('.transcription-status').should('contain', 'En cours')
      cy.get('.progress-bar').should('be.visible')
    })

    it('should transcribe from YouTube URL', () => {
      cy.visit('/transcriptions/new')
      
      // Enter YouTube URL
      cy.get('input[name="youtubeUrl"]').type('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
      
      // Select language
      cy.get('select[name="language"]').select('en')
      
      // Submit
      cy.get('button[type="submit"]').click()
      
      cy.wait('@createTranscription')
      
      // Should show YouTube metadata
      cy.get('.youtube-info').should('be.visible')
      cy.get('.youtube-title').should('exist')
      cy.get('.youtube-thumbnail').should('exist')
    })

    it('should validate file type', () => {
      cy.visit('/transcriptions/new')
      
      // Try to upload invalid file
      cy.uploadFile('document.pdf', 'application/pdf')
      
      cy.get('.error-message').should('contain', 'Format de fichier non supporté')
      cy.get('button[type="submit"]').should('be.disabled')
    })

    it('should validate file size', () => {
      cy.visit('/transcriptions/new')
      
      // Try to upload large file
      cy.uploadFile('large-audio.mp3', 'audio/mp3')
      
      cy.get('.error-message').should('contain', 'Fichier trop volumineux')
      cy.get('button[type="submit"]').should('be.disabled')
    })
  })

  describe('Transcription List', () => {
    it('should display list of transcriptions', () => {
      cy.visit('/transcriptions')
      
      cy.get('h1').should('contain', 'Mes Transcriptions')
      cy.get('.transcription-card').should('have.length.at.least', 1)
    })

    it('should filter transcriptions by status', () => {
      cy.visit('/transcriptions')
      
      // Filter by completed
      cy.get('select[name="status"]').select('completed')
      cy.get('.transcription-card').each($card => {
        cy.wrap($card).find('.status-badge').should('contain', 'Terminé')
      })
      
      // Filter by processing
      cy.get('select[name="status"]').select('processing')
      cy.get('.transcription-card').each($card => {
        cy.wrap($card).find('.status-badge').should('contain', 'En cours')
      })
    })

    it('should search transcriptions', () => {
      cy.visit('/transcriptions')
      
      cy.get('input[name="search"]').type('meeting')
      cy.get('.transcription-card').should('have.length.at.least', 1)
      cy.get('.transcription-card').first().should('contain', 'meeting')
    })

    it('should paginate results', () => {
      cy.visit('/transcriptions')
      
      // Check pagination exists
      cy.get('.pagination').should('exist')
      
      // Go to next page
      cy.get('.pagination-next').click()
      cy.url().should('include', 'page=2')
      
      // Go back
      cy.get('.pagination-prev').click()
      cy.url().should('include', 'page=1')
    })
  })

  describe('Transcription Details', () => {
    it('should display transcription details', () => {
      cy.visit('/transcriptions')
      cy.get('.transcription-card').first().click()
      
      cy.get('.transcription-text').should('exist')
      cy.get('.transcription-metadata').should('exist')
      cy.get('.audio-player').should('exist')
    })

    it('should play audio', () => {
      cy.visit('/transcriptions/123')
      
      cy.get('.audio-player .play-button').click()
      cy.get('.audio-player .pause-button').should('be.visible')
      cy.get('.audio-player .progress-bar').should('exist')
    })

    it('should copy transcription text', () => {
      cy.visit('/transcriptions/123')
      
      cy.get('.copy-button').click()
      cy.get('.notification-success').should('contain', 'Texte copié')
    })

    it('should download transcription', () => {
      cy.visit('/transcriptions/123')
      
      // Download as TXT
      cy.get('.download-button').click()
      cy.get('.download-txt').click()
      
      // Check download started
      cy.readFile('cypress/downloads/transcription-123.txt').should('exist')
    })

    it('should delete transcription', () => {
      cy.visit('/transcriptions/123')
      
      cy.get('.delete-button').click()
      cy.get('.confirm-dialog').should('be.visible')
      cy.get('.confirm-delete').click()
      
      cy.wait('@deleteTranscription')
      cy.url().should('include', '/transcriptions')
      cy.get('.notification-success').should('contain', 'Transcription supprimée')
    })
  })

  describe('Real-time Updates', () => {
    it('should show real-time progress updates', () => {
      cy.visit('/transcriptions/new')
      
      // Start transcription
      cy.uploadFile('sample-audio.mp3', 'audio/mp3')
      cy.get('button[type="submit"]').click()
      
      // Check for WebSocket connection
      cy.window().its('WebSocket').should('exist')
      
      // Progress should update
      cy.get('.progress-percentage').should('contain', '0%')
      cy.get('.progress-percentage', { timeout: 10000 }).should('not.contain', '0%')
    })

    it('should receive completion notification', () => {
      cy.visit('/transcriptions/processing-123')
      
      // Wait for completion
      cy.get('.transcription-status', { timeout: 30000 }).should('contain', 'Terminé')
      cy.get('.notification-success').should('contain', 'Transcription terminée')
    })
  })

  describe('Batch Operations', () => {
    it('should select multiple transcriptions', () => {
      cy.visit('/transcriptions')
      
      // Enable selection mode
      cy.get('.select-mode-toggle').click()
      
      // Select multiple items
      cy.get('.transcription-checkbox').eq(0).check()
      cy.get('.transcription-checkbox').eq(1).check()
      cy.get('.transcription-checkbox').eq(2).check()
      
      cy.get('.selected-count').should('contain', '3 sélectionnés')
    })

    it('should delete multiple transcriptions', () => {
      cy.visit('/transcriptions')
      
      // Select items
      cy.get('.select-mode-toggle').click()
      cy.get('.transcription-checkbox').eq(0).check()
      cy.get('.transcription-checkbox').eq(1).check()
      
      // Delete
      cy.get('.batch-delete').click()
      cy.get('.confirm-dialog').should('be.visible')
      cy.get('.confirm-delete').click()
      
      cy.wait('@batchDelete')
      cy.get('.notification-success').should('contain', '2 transcriptions supprimées')
    })

    it('should export multiple transcriptions', () => {
      cy.visit('/transcriptions')
      
      // Select items
      cy.get('.select-mode-toggle').click()
      cy.get('.select-all').check()
      
      // Export
      cy.get('.batch-export').click()
      cy.get('.export-format-zip').click()
      
      cy.readFile('cypress/downloads/transcriptions-export.zip').should('exist')
    })
  })
})