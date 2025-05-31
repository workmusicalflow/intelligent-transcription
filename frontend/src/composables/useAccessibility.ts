import { ref, nextTick, onMounted, onUnmounted } from 'vue'

/**
 * Composable pour la gestion de l'accessibilité
 */
export function useAccessibility() {
  const announcements = ref<string[]>([])
  const isHighContrast = ref(false)
  const isReducedMotion = ref(false)
  const fontSize = ref('normal')

  // Détection des préférences utilisateur
  const detectUserPreferences = () => {
    // Préférence pour le mouvement réduit
    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
    isReducedMotion.value = motionQuery.matches

    // Préférence pour le contraste élevé
    const contrastQuery = window.matchMedia('(prefers-contrast: high)')
    isHighContrast.value = contrastQuery.matches

    // Écouter les changements
    motionQuery.addEventListener('change', (e) => {
      isReducedMotion.value = e.matches
      updateMotionPreference()
    })

    contrastQuery.addEventListener('change', (e) => {
      isHighContrast.value = e.matches
      updateContrastPreference()
    })
  }

  // Appliquer les préférences de mouvement
  const updateMotionPreference = () => {
    if (isReducedMotion.value) {
      document.documentElement.style.setProperty('--animation-duration', '0.01ms')
      document.documentElement.style.setProperty('--transition-duration', '0.01ms')
    } else {
      document.documentElement.style.removeProperty('--animation-duration')
      document.documentElement.style.removeProperty('--transition-duration')
    }
  }

  // Appliquer les préférences de contraste
  const updateContrastPreference = () => {
    if (isHighContrast.value) {
      document.documentElement.classList.add('high-contrast')
    } else {
      document.documentElement.classList.remove('high-contrast')
    }
  }

  // Annoncer du contenu aux lecteurs d'écran
  const announce = (message: string, priority: 'polite' | 'assertive' = 'polite') => {
    announcements.value.push(message)
    
    nextTick(() => {
      const announcer = document.createElement('div')
      announcer.setAttribute('aria-live', priority)
      announcer.setAttribute('aria-atomic', 'true')
      announcer.setAttribute('class', 'sr-only')
      announcer.textContent = message
      
      document.body.appendChild(announcer)
      
      setTimeout(() => {
        document.body.removeChild(announcer)
        const index = announcements.value.indexOf(message)
        if (index > -1) {
          announcements.value.splice(index, 1)
        }
      }, 1000)
    })
  }

  // Gérer le focus pour les éléments dynamiques
  const focusElement = (selector: string | Element, delay: number = 100) => {
    setTimeout(() => {
      const element = typeof selector === 'string' 
        ? document.querySelector(selector) as HTMLElement
        : selector as HTMLElement
      
      if (element && typeof element.focus === 'function') {
        element.focus()
      }
    }, delay)
  }

  // Gestion du focus trap pour les modales
  const trapFocus = (container: HTMLElement) => {
    const focusableElements = container.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    ) as NodeListOf<HTMLElement>
    
    const firstFocusable = focusableElements[0]
    const lastFocusable = focusableElements[focusableElements.length - 1]

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Tab') {
        if (e.shiftKey) {
          if (document.activeElement === firstFocusable) {
            e.preventDefault()
            lastFocusable.focus()
          }
        } else {
          if (document.activeElement === lastFocusable) {
            e.preventDefault()
            firstFocusable.focus()
          }
        }
      }
      
      if (e.key === 'Escape') {
        e.preventDefault()
        // Émettre un événement personnalisé pour fermer la modale
        container.dispatchEvent(new CustomEvent('close-modal'))
      }
    }

    container.addEventListener('keydown', handleKeyDown)
    
    // Focus le premier élément
    if (firstFocusable) {
      firstFocusable.focus()
    }

    return () => {
      container.removeEventListener('keydown', handleKeyDown)
    }
  }

  // Gérer la taille de police
  const setFontSize = (size: 'small' | 'normal' | 'large' | 'extra-large') => {
    fontSize.value = size
    const root = document.documentElement
    
    switch (size) {
      case 'small':
        root.style.setProperty('--base-font-size', '14px')
        break
      case 'normal':
        root.style.setProperty('--base-font-size', '16px')
        break
      case 'large':
        root.style.setProperty('--base-font-size', '18px')
        break
      case 'extra-large':
        root.style.setProperty('--base-font-size', '20px')
        break
    }
  }

  // Détecter l'utilisation du clavier pour la navigation
  const isUsingKeyboard = ref(false)

  const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Tab') {
      isUsingKeyboard.value = true
      document.documentElement.classList.add('using-keyboard')
    }
  }

  const handleMouseDown = () => {
    isUsingKeyboard.value = false
    document.documentElement.classList.remove('using-keyboard')
  }

  // Générer des IDs uniques pour l'accessibilité
  let idCounter = 0
  const generateId = (prefix: string = 'a11y') => {
    return `${prefix}-${++idCounter}-${Date.now()}`
  }

  onMounted(() => {
    detectUserPreferences()
    updateMotionPreference()
    updateContrastPreference()
    
    document.addEventListener('keydown', handleKeyDown)
    document.addEventListener('mousedown', handleMouseDown)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown)
    document.removeEventListener('mousedown', handleMouseDown)
  })

  return {
    // État
    announcements,
    isHighContrast,
    isReducedMotion,
    fontSize,
    isUsingKeyboard,
    
    // Méthodes
    announce,
    focusElement,
    trapFocus,
    setFontSize,
    generateId,
    updateMotionPreference,
    updateContrastPreference
  }
}

/**
 * Directives Vue pour l'accessibilité
 */
export const accessibilityDirectives = {
  // Directive pour auto-focus
  focus: {
    mounted(el: HTMLElement, binding: any) {
      if (binding.value !== false) {
        nextTick(() => {
          el.focus()
        })
      }
    }
  },
  
  // Directive pour annoncer les changements
  announce: {
    updated(el: HTMLElement, binding: any) {
      if (binding.value && binding.value !== binding.oldValue) {
        const { announce } = useAccessibility()
        announce(binding.value, binding.modifiers.assertive ? 'assertive' : 'polite')
      }
    }
  }
}