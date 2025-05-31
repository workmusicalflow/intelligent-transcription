import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import { nanoid } from 'nanoid'
import type { UIState, Notification, Modal, NotificationAction } from '@/types'

export const useUIStore = defineStore('ui', () => {
  // State
  const sidebarOpen = ref(false)
  const theme = ref<'light' | 'dark' | 'system'>('system')
  const notifications = ref<Notification[]>([])
  const modals = ref<Modal[]>([])
  const loading = ref<Record<string, boolean>>({})
  
  // Getters
  const activeNotifications = computed(() => notifications.value.filter(n => !n.dismissed))
  const hasActiveModals = computed(() => modals.value.length > 0)
  const currentTheme = computed(() => {
    if (theme.value === 'system') {
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }
    return theme.value
  })
  
  // Actions
  const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
  }
  
  const openSidebar = () => {
    sidebarOpen.value = true
  }
  
  const closeSidebar = () => {
    sidebarOpen.value = false
  }
  
  const setTheme = (newTheme: typeof theme.value) => {
    theme.value = newTheme
    localStorage.setItem('theme', newTheme)
    applyTheme()
  }
  
  const applyTheme = () => {
    const html = document.documentElement
    
    if (currentTheme.value === 'dark') {
      html.classList.add('dark')
    } else {
      html.classList.remove('dark')
    }
  }
  
  const showNotification = (notification: Omit<Notification, 'id'>) => {
    const newNotification: Notification = {
      ...notification,
      id: nanoid(),
      duration: notification.duration ?? 5000
    }
    
    notifications.value.push(newNotification)
    
    // Auto-dismiss notification
    if (newNotification.duration && newNotification.duration > 0) {
      setTimeout(() => {
        dismissNotification(newNotification.id)
      }, newNotification.duration)
    }
    
    return newNotification.id
  }
  
  const dismissNotification = (id: string) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }
  
  const clearAllNotifications = () => {
    notifications.value = []
  }
  
  const showModal = (modal: Omit<Modal, 'id'>) => {
    const newModal: Modal = {
      ...modal,
      id: nanoid()
    }
    
    modals.value.push(newModal)
    
    return newModal.id
  }
  
  const closeModal = (id: string) => {
    const index = modals.value.findIndex(m => m.id === id)
    if (index > -1) {
      modals.value.splice(index, 1)
    }
  }
  
  const closeAllModals = () => {
    modals.value = []
  }
  
  const setLoading = (key: string, value: boolean) => {
    if (value) {
      loading.value[key] = true
    } else {
      delete loading.value[key]
    }
  }
  
  const isLoading = (key: string) => {
    return loading.value[key] || false
  }
  
  const hasAnyLoading = computed(() => Object.keys(loading.value).length > 0)
  
  // Success notifications
  const showSuccess = (title: string, message: string, actions?: NotificationAction[]) => {
    return showNotification({
      type: 'success',
      title,
      message,
      actions
    })
  }
  
  // Error notifications
  const showError = (title: string, message: string, actions?: NotificationAction[]) => {
    return showNotification({
      type: 'error',
      title,
      message,
      duration: 8000, // Longer duration for errors
      actions
    })
  }
  
  // Warning notifications
  const showWarning = (title: string, message: string, actions?: NotificationAction[]) => {
    return showNotification({
      type: 'warning',
      title,
      message,
      duration: 6000,
      actions
    })
  }
  
  // Info notifications
  const showInfo = (title: string, message: string, actions?: NotificationAction[]) => {
    return showNotification({
      type: 'info',
      title,
      message,
      actions
    })
  }
  
  // Confirmation modal
  const showConfirmation = (
    title: string,
    message: string,
    confirmText = 'Confirmer',
    cancelText = 'Annuler'
  ): Promise<boolean> => {
    return new Promise((resolve) => {
      showModal({
        component: 'ConfirmationModal',
        props: {
          title,
          message,
          confirmText,
          cancelText,
          onConfirm: () => resolve(true),
          onCancel: () => resolve(false)
        },
        persistent: true
      })
    })
  }
  
  // Initialize theme
  const initializeTheme = () => {
    const savedTheme = localStorage.getItem('theme') as typeof theme.value
    if (savedTheme && ['light', 'dark', 'system'].includes(savedTheme)) {
      theme.value = savedTheme
    }
    applyTheme()
    
    // Watch for system theme changes
    if (theme.value === 'system') {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
      mediaQuery.addEventListener('change', applyTheme)
    }
  }
  
  // Watch theme changes
  watch(theme, applyTheme)
  
  return {
    // State
    sidebarOpen,
    theme,
    notifications,
    modals,
    loading,
    
    // Getters
    activeNotifications,
    hasActiveModals,
    currentTheme,
    hasAnyLoading,
    
    // Actions
    toggleSidebar,
    openSidebar,
    closeSidebar,
    setTheme,
    applyTheme,
    showNotification,
    dismissNotification,
    clearAllNotifications,
    showModal,
    closeModal,
    closeAllModals,
    setLoading,
    isLoading,
    
    // Helper methods
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showConfirmation,
    initializeTheme
  }
})