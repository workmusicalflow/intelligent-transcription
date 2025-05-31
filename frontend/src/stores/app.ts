import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types'

export const useAppStore = defineStore('app', () => {
  // State
  const isLoading = ref(false)
  const isInitialized = ref(false)
  const version = ref(import.meta.env.VITE_APP_VERSION || '1.0.0')
  const apiBaseUrl = ref(import.meta.env.VITE_API_BASE_URL || '/api/v2')
  const graphqlEndpoint = ref(import.meta.env.VITE_GRAPHQL_ENDPOINT || '/graphql')
  const connectionStatus = ref<'online' | 'offline' | 'reconnecting'>('online')
  
  // Getters
  const isOnline = computed(() => connectionStatus.value === 'online')
  const isOffline = computed(() => connectionStatus.value === 'offline')
  
  // Actions
  const setLoading = (loading: boolean) => {
    isLoading.value = loading
  }
  
  const setConnectionStatus = (status: typeof connectionStatus.value) => {
    connectionStatus.value = status
  }
  
  const initialize = async () => {
    try {
      setLoading(true)
      
      // Initialize app settings
      await loadAppSettings()
      
      // Check connectivity
      await checkConnectivity()
      
      isInitialized.value = true
    } catch (error) {
      console.error('Failed to initialize app:', error)
    } finally {
      setLoading(false)
    }
  }
  
  const loadAppSettings = async () => {
    // Load any global app settings from API or localStorage
    try {
      // Example: load feature flags, global config, etc.
      const settings = localStorage.getItem('app-settings')
      if (settings) {
        const parsed = JSON.parse(settings)
        // Apply settings...
      }
    } catch (error) {
      console.error('Failed to load app settings:', error)
    }
  }
  
  const checkConnectivity = async () => {
    try {
      const response = await fetch('/api/v2/health', {
        method: 'HEAD',
        cache: 'no-cache'
      })
      
      if (response.ok) {
        setConnectionStatus('online')
      } else {
        setConnectionStatus('offline')
      }
    } catch (error) {
      setConnectionStatus('offline')
    }
  }
  
  const startConnectivityMonitoring = () => {
    // Monitor online/offline status
    window.addEventListener('online', () => {
      setConnectionStatus('online')
    })
    
    window.addEventListener('offline', () => {
      setConnectionStatus('offline')
    })
    
    // Periodic connectivity check
    setInterval(checkConnectivity, 30000) // Check every 30 seconds
  }
  
  const showGlobalError = (error: Error | string) => {
    const message = typeof error === 'string' ? error : error.message
    console.error('Global error:', message)
    
    // You can integrate with notification system here
    // notificationStore.addNotification({
    //   type: 'error',
    //   title: 'Erreur',
    //   message
    // })
  }
  
  return {
    // State
    isLoading,
    isInitialized,
    version,
    apiBaseUrl,
    graphqlEndpoint,
    connectionStatus,
    
    // Getters
    isOnline,
    isOffline,
    
    // Actions
    setLoading,
    setConnectionStatus,
    initialize,
    loadAppSettings,
    checkConnectivity,
    startConnectivityMonitoring,
    showGlobalError
  }
})