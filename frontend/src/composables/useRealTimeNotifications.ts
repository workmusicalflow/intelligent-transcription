import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useGlobalWebSocket } from './useWebSocket'
import { useUIStore } from '@stores/ui'
import { useAuthStore } from '@stores/auth'
import type { Notification } from '@/types'

interface RealTimeNotification {
  id: string
  type: 'transcription' | 'chat' | 'system' | 'user'
  title: string
  message: string
  data?: any
  timestamp: string
  read: boolean
  priority: 'low' | 'medium' | 'high'
  actions?: Array<{
    label: string
    action: string
    data?: any
  }>
}

interface NotificationState {
  notifications: RealTimeNotification[]
  unreadCount: number
  lastSeen: string | null
  soundEnabled: boolean
  pushEnabled: boolean
}

export function useRealTimeNotifications() {
  const webSocket = useGlobalWebSocket()
  const uiStore = useUIStore()
  const authStore = useAuthStore()
  
  // State
  const state = reactive<NotificationState>({
    notifications: [],
    unreadCount: 0,
    lastSeen: localStorage.getItem('notifications-last-seen'),
    soundEnabled: localStorage.getItem('notifications-sound') !== 'false',
    pushEnabled: localStorage.getItem('notifications-push') !== 'false'
  })
  
  const isSubscribed = ref(false)
  const maxNotifications = 100
  
  // Notification handlers
  const handleIncomingNotification = (notification: RealTimeNotification) => {
    // Add to state
    state.notifications.unshift(notification)
    
    // Limit number of stored notifications
    if (state.notifications.length > maxNotifications) {
      state.notifications = state.notifications.slice(0, maxNotifications)
    }
    
    // Update unread count
    updateUnreadCount()
    
    // Show in-app notification
    showInAppNotification(notification)
    
    // Play sound if enabled
    if (state.soundEnabled && notification.priority !== 'low') {
      playNotificationSound(notification.priority)
    }
    
    // Show browser notification if enabled and user preferences allow
    if (state.pushEnabled && document.visibilityState === 'hidden') {
      showBrowserNotification(notification)
    }
    
    // Store in localStorage for persistence
    saveNotificationsToStorage()
  }
  
  const showInAppNotification = (notification: RealTimeNotification) => {
    const notificationType = getUINotificationType(notification.type, notification.priority)
    
    uiStore.showNotification({
      type: notificationType,
      title: notification.title,
      message: notification.message,
      duration: getNotificationDuration(notification.priority),
      actions: notification.actions?.map(action => ({
        label: action.label,
        action: () => handleNotificationAction(action, notification)
      }))
    })
  }
  
  const showBrowserNotification = async (notification: RealTimeNotification) => {
    if (!('Notification' in window)) return
    
    if (Notification.permission === 'granted') {
      const browserNotification = new Notification(notification.title, {
        body: notification.message,
        icon: '/favicon.ico',
        tag: notification.id,
        requireInteraction: notification.priority === 'high'
      })
      
      browserNotification.onclick = () => {
        window.focus()
        handleNotificationClick(notification)
        browserNotification.close()
      }
      
      // Auto-close after delay
      setTimeout(() => {
        browserNotification.close()
      }, getNotificationDuration(notification.priority))
    }
  }
  
  const playNotificationSound = (priority: 'low' | 'medium' | 'high') => {
    try {
      const audio = new Audio()
      
      switch (priority) {
        case 'high':
          audio.src = '/sounds/notification-urgent.mp3'
          break
        case 'medium':
          audio.src = '/sounds/notification-medium.mp3'
          break
        default:
          audio.src = '/sounds/notification-soft.mp3'
      }
      
      audio.volume = 0.5
      audio.play().catch(console.error)
    } catch (error) {
      console.error('Failed to play notification sound:', error)
    }
  }
  
  // Helper functions
  const getUINotificationType = (type: string, priority: string): 'success' | 'error' | 'warning' | 'info' => {
    switch (type) {
      case 'transcription':
        return priority === 'high' ? 'error' : 'success'
      case 'system':
        return priority === 'high' ? 'error' : 'warning'
      default:
        return 'info'
    }
  }
  
  const getNotificationDuration = (priority: string): number => {
    switch (priority) {
      case 'high': return 10000
      case 'medium': return 6000
      default: return 4000
    }
  }
  
  const handleNotificationAction = (action: any, notification: RealTimeNotification) => {
    switch (action.action) {
      case 'view-transcription':
        window.location.href = `/transcriptions/${action.data?.transcriptionId}`
        break
      case 'open-chat':
        window.location.href = `/chat/${action.data?.conversationId}`
        break
      case 'dismiss':
        markAsRead(notification.id)
        break
      default:
        console.log('Unknown notification action:', action)
    }
  }
  
  const handleNotificationClick = (notification: RealTimeNotification) => {
    markAsRead(notification.id)
    
    // Default click behavior based on notification type
    switch (notification.type) {
      case 'transcription':
        if (notification.data?.transcriptionId) {
          window.location.href = `/transcriptions/${notification.data.transcriptionId}`
        }
        break
      case 'chat':
        if (notification.data?.conversationId) {
          window.location.href = `/chat/${notification.data.conversationId}`
        }
        break
    }
  }
  
  // Notification management
  const markAsRead = (notificationId: string) => {
    const notification = state.notifications.find(n => n.id === notificationId)
    if (notification && !notification.read) {
      notification.read = true
      updateUnreadCount()
      saveNotificationsToStorage()
    }
  }
  
  const markAllAsRead = () => {
    state.notifications.forEach(n => n.read = true)
    state.unreadCount = 0
    state.lastSeen = new Date().toISOString()
    localStorage.setItem('notifications-last-seen', state.lastSeen)
    saveNotificationsToStorage()
  }
  
  const deleteNotification = (notificationId: string) => {
    const index = state.notifications.findIndex(n => n.id === notificationId)
    if (index > -1) {
      state.notifications.splice(index, 1)
      updateUnreadCount()
      saveNotificationsToStorage()
    }
  }
  
  const clearAllNotifications = () => {
    state.notifications = []
    state.unreadCount = 0
    saveNotificationsToStorage()
  }
  
  const updateUnreadCount = () => {
    state.unreadCount = state.notifications.filter(n => !n.read).length
  }
  
  // Settings
  const setSoundEnabled = (enabled: boolean) => {
    state.soundEnabled = enabled
    localStorage.setItem('notifications-sound', enabled.toString())
  }
  
  const setPushEnabled = async (enabled: boolean) => {
    if (enabled && 'Notification' in window) {
      const permission = await Notification.requestPermission()
      if (permission !== 'granted') {
        enabled = false
      }
    }
    
    state.pushEnabled = enabled
    localStorage.setItem('notifications-push', enabled.toString())
  }
  
  // Persistence
  const saveNotificationsToStorage = () => {
    try {
      const dataToSave = {
        notifications: state.notifications.slice(0, 50), // Limit stored notifications
        lastSeen: state.lastSeen
      }
      localStorage.setItem('notifications-data', JSON.stringify(dataToSave))
    } catch (error) {
      console.error('Failed to save notifications to storage:', error)
    }
  }
  
  const loadNotificationsFromStorage = () => {
    try {
      const data = localStorage.getItem('notifications-data')
      if (data) {
        const parsed = JSON.parse(data)
        state.notifications = parsed.notifications || []
        state.lastSeen = parsed.lastSeen
        updateUnreadCount()
      }
    } catch (error) {
      console.error('Failed to load notifications from storage:', error)
    }
  }
  
  // WebSocket subscription
  const subscribe = () => {
    if (isSubscribed.value || !webSocket) return
    
    // Subscribe to user notifications
    webSocket.subscribeToUserNotifications(handleIncomingNotification)
    
    // Subscribe to specific notification types
    webSocket.on('notification:transcription', (data: any) => {
      handleIncomingNotification({
        id: data.id || Date.now().toString(),
        type: 'transcription',
        title: data.title || 'Notification de transcription',
        message: data.message,
        data: data.data,
        timestamp: data.timestamp || new Date().toISOString(),
        read: false,
        priority: data.priority || 'medium',
        actions: data.actions
      })
    })
    
    webSocket.on('notification:chat', (data: any) => {
      handleIncomingNotification({
        id: data.id || Date.now().toString(),
        type: 'chat',
        title: data.title || 'Nouveau message',
        message: data.message,
        data: data.data,
        timestamp: data.timestamp || new Date().toISOString(),
        read: false,
        priority: data.priority || 'medium',
        actions: data.actions
      })
    })
    
    webSocket.on('notification:system', (data: any) => {
      handleIncomingNotification({
        id: data.id || Date.now().toString(),
        type: 'system',
        title: data.title || 'Notification système',
        message: data.message,
        data: data.data,
        timestamp: data.timestamp || new Date().toISOString(),
        read: false,
        priority: data.priority || 'high',
        actions: data.actions
      })
    })
    
    isSubscribed.value = true
  }
  
  const unsubscribe = () => {
    if (!isSubscribed.value || !webSocket) return
    
    webSocket.off('notification:transcription')
    webSocket.off('notification:chat')
    webSocket.off('notification:system')
    
    isSubscribed.value = false
  }
  
  // Lifecycle
  onMounted(() => {
    loadNotificationsFromStorage()
    
    if (authStore.isAuthenticated) {
      subscribe()
    }
    
    // Request notification permission on first visit
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission()
    }
  })
  
  onUnmounted(() => {
    unsubscribe()
  })
  
  return {
    // State
    state,
    isSubscribed,
    
    // Methods
    subscribe,
    unsubscribe,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    clearAllNotifications,
    setSoundEnabled,
    setPushEnabled
  }
}

// Global notifications instance
let globalNotifications: ReturnType<typeof useRealTimeNotifications> | null = null

export function useGlobalNotifications() {
  if (!globalNotifications) {
    globalNotifications = useRealTimeNotifications()
  }
  return globalNotifications
}

export default useRealTimeNotifications