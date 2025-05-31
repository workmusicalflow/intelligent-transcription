import * as Sentry from '@sentry/vue'
import { App } from 'vue'
import { Router } from 'vue-router'

export function setupSentry(app: App, router: Router) {
  // Only initialize in production
  if (import.meta.env.PROD && import.meta.env.VITE_SENTRY_DSN) {
    Sentry.init({
      app,
      dsn: import.meta.env.VITE_SENTRY_DSN,
      environment: import.meta.env.VITE_APP_ENV || 'production',
      release: import.meta.env.VITE_APP_VERSION || '1.0.0',
      
      // Integration configuration
      integrations: [
        Sentry.browserTracingIntegration({ router }),
        Sentry.replayIntegration({
          maskAllText: false,
          blockAllMedia: false,
          // Only record sessions with errors
          sessionSampleRate: 0,
          errorSampleRate: 1.0
        })
      ],
      
      // Performance monitoring
      tracesSampleRate: import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE 
        ? parseFloat(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE) 
        : 0.1,
      
      // Capture replay on errors
      replaysSessionSampleRate: 0,
      replaysOnErrorSampleRate: 1.0,
      
      // Additional options
      beforeSend(event, hint) {
        // Filter out certain errors
        const error = hint.originalException
        
        // Don't send network errors in development
        if (error && error instanceof Error) {
          if (error.message?.includes('Network Error')) {
            return null
          }
          
          // Don't send cancelled requests
          if (error.message?.includes('cancelled')) {
            return null
          }
        }
        
        // Add user context if available
        const user = localStorage.getItem('user')
        if (user) {
          try {
            const userData = JSON.parse(user)
            event.user = {
              id: userData.id,
              email: userData.email,
              username: userData.name
            }
          } catch (e) {
            // Ignore parse errors
          }
        }
        
        return event
      },
      
      // Ignore certain errors
      ignoreErrors: [
        // Browser extensions
        'top.GLOBALS',
        'ResizeObserver loop limit exceeded',
        'Non-Error promise rejection captured',
        // Random network errors
        'Network request failed',
        'NetworkError',
        'Failed to fetch',
        // Vue specific
        'NavigationDuplicated'
      ],
      
      // Don't capture errors from these domains
      denyUrls: [
        // Browser extensions
        /extensions\//i,
        /^chrome:\/\//i,
        /^chrome-extension:\/\//i,
        /^moz-extension:\/\//i,
        // Common crawlers
        /googlebot/i,
        /bingbot/i
      ]
    })
  }
}

// Helper to capture custom events
export function captureEvent(message: string, level: Sentry.SeverityLevel = 'info', extra?: Record<string, any>) {
  if (import.meta.env.PROD) {
    Sentry.captureMessage(message, {
      level,
      extra
    })
  } else {
    console.log(`[Sentry ${level}]`, message, extra)
  }
}

// Helper to capture exceptions with context
export function captureException(error: Error, context?: Record<string, any>) {
  if (import.meta.env.PROD) {
    Sentry.captureException(error, {
      contexts: {
        custom: context
      }
    })
  } else {
    console.error('[Sentry Error]', error, context)
  }
}

// Set user context
export function setUser(user: { id: string; email: string; name?: string } | null) {
  if (import.meta.env.PROD) {
    if (user) {
      Sentry.setUser({
        id: user.id,
        email: user.email,
        username: user.name
      })
    } else {
      Sentry.setUser(null)
    }
  }
}

// Add breadcrumb for tracking user actions
export function addBreadcrumb(
  message: string, 
  category: string, 
  level: Sentry.SeverityLevel = 'info',
  data?: Record<string, any>
) {
  if (import.meta.env.PROD) {
    Sentry.addBreadcrumb({
      message,
      category,
      level,
      data,
      timestamp: Date.now() / 1000
    })
  }
}

// Performance monitoring
export function startTransaction(name: string, op: string = 'navigation') {
  if (import.meta.env.PROD) {
    return Sentry.startInactiveSpan({
      name,
      op
    })
  }
  return null
}

// Track specific user interactions
export const trackInteraction = {
  transcriptionStarted: (type: 'file' | 'youtube', language: string) => {
    addBreadcrumb('Transcription started', 'transcription', 'info', { type, language })
  },
  
  transcriptionCompleted: (id: string, duration: number) => {
    addBreadcrumb('Transcription completed', 'transcription', 'info', { id, duration })
  },
  
  transcriptionFailed: (id: string, error: string) => {
    addBreadcrumb('Transcription failed', 'transcription', 'error', { id, error })
  },
  
  chatMessageSent: (conversationId: string) => {
    addBreadcrumb('Chat message sent', 'chat', 'info', { conversationId })
  },
  
  fileUploaded: (fileName: string, fileSize: number) => {
    addBreadcrumb('File uploaded', 'upload', 'info', { fileName, fileSize })
  },
  
  featureUsed: (featureName: string, metadata?: Record<string, any>) => {
    addBreadcrumb(`Feature used: ${featureName}`, 'feature', 'info', metadata)
  }
}