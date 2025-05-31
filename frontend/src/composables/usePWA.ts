import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRegisterSW } from 'virtual:pwa-register/vue'
import { useUIStore } from '@stores/ui'

interface BeforeInstallPromptEvent extends Event {
  readonly platforms: string[]
  readonly userChoice: Promise<{
    outcome: 'accepted' | 'dismissed'
    platform: string
  }>
  prompt(): Promise<void>
}

interface PWAState {
  isInstalled: boolean
  isInstallable: boolean
  isUpdateAvailable: boolean
  isOnline: boolean
  installPrompt: BeforeInstallPromptEvent | null
}

export function usePWA() {
  const uiStore = useUIStore()
  
  // PWA Registration
  const {
    needRefresh,
    updateServiceWorker,
    offlineReady
  } = useRegisterSW({
    onRegistered(registration) {
      console.log('SW Registered:', registration)
    },
    onRegisterError(error) {
      console.error('SW registration error:', error)
    },
    onNeedRefresh() {
      console.log('SW needs refresh')
      showUpdateNotification()
    },
    onOfflineReady() {
      console.log('App ready to work offline')
      showOfflineReadyNotification()
    }
  })
  
  // State
  const state = ref<PWAState>({
    isInstalled: false,
    isInstallable: false,
    isUpdateAvailable: false,
    isOnline: navigator.onLine,
    installPrompt: null
  })
  
  const installationStatus = ref<'idle' | 'installing' | 'installed' | 'failed'>('idle')
  
  // Computed
  const canInstall = computed(() => {
    return state.value.isInstallable && !state.value.isInstalled && state.value.installPrompt
  })
  
  const isStandalone = computed(() => {
    return window.matchMedia('(display-mode: standalone)').matches ||
           (window.navigator as any).standalone === true
  })
  
  const supportsPWA = computed(() => {
    return 'serviceWorker' in navigator && 'PushManager' in window
  })
  
  // Methods
  const installApp = async () => {
    if (!canInstall.value || !state.value.installPrompt) {
      return false
    }
    
    try {
      installationStatus.value = 'installing'
      
      // Show the install prompt
      await state.value.installPrompt.prompt()
      
      // Wait for user choice
      const choiceResult = await state.value.installPrompt.userChoice
      
      if (choiceResult.outcome === 'accepted') {
        state.value.isInstalled = true
        state.value.installPrompt = null
        installationStatus.value = 'installed'
        
        uiStore.showSuccess(
          'Installation réussie',
          'L\'application a été installée avec succès!'
        )
        
        return true
      } else {
        installationStatus.value = 'idle'
        return false
      }
    } catch (error) {
      console.error('Installation failed:', error)
      installationStatus.value = 'failed'
      
      uiStore.showError(
        'Installation échouée',
        'Impossible d\'installer l\'application'
      )
      
      return false
    }
  }
  
  const updateApp = async () => {
    try {
      await updateServiceWorker(true)
      
      uiStore.showSuccess(
        'Mise à jour appliquée',
        'L\'application a été mise à jour avec succès!'
      )
    } catch (error) {
      console.error('Update failed:', error)
      
      uiStore.showError(
        'Mise à jour échouée',
        'Impossible de mettre à jour l\'application'
      )
    }
  }
  
  const showUpdateNotification = () => {
    state.value.isUpdateAvailable = true
    
    uiStore.showNotification({
      type: 'info',
      title: 'Mise à jour disponible',
      message: 'Une nouvelle version de l\'application est disponible',
      duration: 0, // Persistent
      actions: [
        {
          label: 'Mettre à jour',
          action: updateApp
        },
        {
          label: 'Plus tard',
          action: () => {
            state.value.isUpdateAvailable = false
          }
        }
      ]
    })
  }
  
  const showOfflineReadyNotification = () => {
    uiStore.showInfo(
      'Mode hors ligne activé',
      'L\'application peut maintenant fonctionner sans connexion internet'
    )
  }
  
  const showInstallPrompt = () => {
    if (!canInstall.value) return
    
    uiStore.showNotification({
      type: 'info',
      title: 'Installer l\'application',
      message: 'Installez l\'application pour une expérience optimale',
      duration: 10000,
      actions: [
        {
          label: 'Installer',
          action: installApp
        },
        {
          label: 'Non merci',
          action: () => {
            localStorage.setItem('pwa-install-dismissed', 'true')
          }
        }
      ]
    })
  }
  
  // Network status management
  const handleOnline = () => {
    state.value.isOnline = true
    console.log('App is online')
    
    uiStore.showSuccess(
      'Connexion rétablie',
      'Vous êtes de nouveau connecté à internet'
    )
  }
  
  const handleOffline = () => {
    state.value.isOnline = false
    console.log('App is offline')
    
    uiStore.showWarning(
      'Mode hors ligne',
      'Vous travaillez actuellement hors ligne'
    )
  }
  
  // Install prompt handling
  const handleBeforeInstallPrompt = (event: BeforeInstallPromptEvent) => {
    event.preventDefault()
    state.value.installPrompt = event
    state.value.isInstallable = true
    
    // Show install prompt after delay if not dismissed before
    setTimeout(() => {
      const dismissed = localStorage.getItem('pwa-install-dismissed')
      if (!dismissed && !state.value.isInstalled && !isStandalone.value) {
        showInstallPrompt()
      }
    }, 3000)
  }
  
  // App installed handling
  const handleAppInstalled = () => {
    state.value.isInstalled = true
    state.value.isInstallable = false
    state.value.installPrompt = null
    
    console.log('PWA was installed')
  }
  
  // Share API
  const shareContent = async (data: {
    title?: string
    text?: string
    url?: string
    files?: File[]
  }) => {
    if (!navigator.share) {
      // Fallback to clipboard
      if (data.url && navigator.clipboard) {
        await navigator.clipboard.writeText(data.url)
        uiStore.showInfo('Lien copié', 'Le lien a été copié dans le presse-papiers')
      }
      return false
    }
    
    try {
      await navigator.share(data)
      return true
    } catch (error) {
      if ((error as Error).name !== 'AbortError') {
        console.error('Share failed:', error)
      }
      return false
    }
  }
  
  // Notifications permission
  const requestNotificationPermission = async () => {
    if (!('Notification' in window)) {
      return 'not-supported'
    }
    
    if (Notification.permission === 'denied') {
      return 'denied'
    }
    
    if (Notification.permission === 'granted') {
      return 'granted'
    }
    
    const permission = await Notification.requestPermission()
    return permission
  }
  
  // Lifecycle
  onMounted(() => {
    // Check if already installed
    state.value.isInstalled = isStandalone.value
    
    // Event listeners
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
    window.addEventListener('appinstalled', handleAppInstalled)
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
    
    // Check display mode changes
    const mediaQuery = window.matchMedia('(display-mode: standalone)')
    mediaQuery.addEventListener('change', (e) => {
      state.value.isInstalled = e.matches
    })
  })
  
  onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
    window.removeEventListener('appinstalled', handleAppInstalled)
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
  })
  
  return {
    // State
    state,
    installationStatus,
    needRefresh,
    offlineReady,
    
    // Computed
    canInstall,
    isStandalone,
    supportsPWA,
    
    // Methods
    installApp,
    updateApp,
    showInstallPrompt,
    shareContent,
    requestNotificationPermission
  }
}

export default usePWA