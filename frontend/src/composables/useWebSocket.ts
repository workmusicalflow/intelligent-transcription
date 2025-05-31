import { ref, reactive, onMounted, onUnmounted, watch } from 'vue'
import { io, Socket } from 'socket.io-client'
import { useAuthStore } from '@stores/auth'
import { useUIStore } from '@stores/ui'
import type { TranscriptionUpdate, TranscriptionProgress } from '@/types'

interface WebSocketState {
  connected: boolean
  reconnecting: boolean
  error: string | null
}

interface UseWebSocketOptions {
  autoConnect?: boolean
  namespace?: string
  retryAttempts?: number
  retryDelay?: number
}

export function useWebSocket(options: UseWebSocketOptions = {}) {
  const {
    autoConnect = true,
    namespace = '/',
    retryAttempts = 5,
    retryDelay = 1000
  } = options

  const authStore = useAuthStore()
  const uiStore = useUIStore()
  
  // State
  const socket = ref<Socket | null>(null)
  const state = reactive<WebSocketState>({
    connected: false,
    reconnecting: false,
    error: null
  })
  
  const retryCount = ref(0)
  const reconnectTimer = ref<NodeJS.Timeout | null>(null)
  
  // Event handlers storage
  const eventHandlers = new Map<string, Array<(...args: any[]) => void>>()
  
  // Connection methods
  const connect = () => {
    if (socket.value?.connected) {
      return
    }
    
    try {
      state.error = null
      
      const token = localStorage.getItem('auth-token')
      if (!token) {
        state.error = 'No authentication token'
        return
      }
      
      socket.value = io(`${location.origin}${namespace}`, {
        auth: {
          token
        },
        transports: ['websocket', 'polling'],
        upgrade: true,
        rememberUpgrade: true,
        timeout: 20000,
        forceNew: true
      })
      
      setupEventListeners()
      
    } catch (error) {
      state.error = error instanceof Error ? error.message : 'Connection failed'
      console.error('WebSocket connection error:', error)
    }
  }
  
  const disconnect = () => {
    if (reconnectTimer.value) {
      clearTimeout(reconnectTimer.value)
      reconnectTimer.value = null
    }
    
    if (socket.value) {
      socket.value.disconnect()
      socket.value = null
    }
    
    state.connected = false
    state.reconnecting = false
    retryCount.value = 0
  }
  
  const reconnect = () => {
    if (state.reconnecting || retryCount.value >= retryAttempts) {
      return
    }
    
    state.reconnecting = true
    retryCount.value++
    
    const delay = retryDelay * Math.pow(2, retryCount.value - 1) // Exponential backoff
    
    reconnectTimer.value = setTimeout(() => {
      console.log(`Attempting to reconnect (${retryCount.value}/${retryAttempts})...`)
      disconnect()
      connect()
    }, delay)
  }
  
  // Event listener setup
  const setupEventListeners = () => {
    if (!socket.value) return
    
    socket.value.on('connect', () => {
      state.connected = true
      state.reconnecting = false
      state.error = null
      retryCount.value = 0
      
      console.log('WebSocket connected')
      
      // Re-register event handlers
      eventHandlers.forEach((handlers, event) => {
        handlers.forEach(handler => {
          socket.value?.on(event, handler)
        })
      })
    })
    
    socket.value.on('disconnect', (reason) => {
      state.connected = false
      console.log('WebSocket disconnected:', reason)
      
      // Attempt reconnection for certain disconnect reasons
      if (['io server disconnect', 'io client disconnect'].includes(reason)) {
        // Manual disconnect, don't reconnect
      } else {
        reconnect()
      }
    })
    
    socket.value.on('connect_error', (error) => {
      state.error = error.message
      state.connected = false
      console.error('WebSocket connection error:', error)
      
      if (error.message.includes('authentication')) {
        // Auth error, redirect to login
        authStore.logout()
      } else {
        reconnect()
      }
    })
    
    socket.value.on('error', (error) => {
      state.error = error.message || 'WebSocket error'
      console.error('WebSocket error:', error)
    })
  }
  
  // Event subscription methods
  const on = (event: string, handler: (...args: any[]) => void) => {
    if (!eventHandlers.has(event)) {
      eventHandlers.set(event, [])
    }
    eventHandlers.get(event)!.push(handler)
    
    if (socket.value?.connected) {
      socket.value.on(event, handler)
    }
  }
  
  const off = (event: string, handler?: (...args: any[]) => void) => {
    if (handler) {
      const handlers = eventHandlers.get(event)
      if (handlers) {
        const index = handlers.indexOf(handler)
        if (index > -1) {
          handlers.splice(index, 1)
        }
      }
      socket.value?.off(event, handler)
    } else {
      eventHandlers.delete(event)
      socket.value?.off(event)
    }
  }
  
  const emit = (event: string, data?: any) => {
    if (socket.value?.connected) {
      socket.value.emit(event, data)
    } else {
      console.warn('Cannot emit event: WebSocket not connected')
    }
  }
  
  // Specific subscription methods
  const subscribeToTranscriptionUpdates = (transcriptionId: string, callback: (update: TranscriptionUpdate) => void) => {
    const event = `transcription:${transcriptionId}:update`
    on(event, callback)
    
    // Join the transcription room
    emit('join-transcription', { transcriptionId })
    
    return () => {
      off(event, callback)
      emit('leave-transcription', { transcriptionId })
    }
  }
  
  const subscribeToTranscriptionProgress = (transcriptionId: string, callback: (progress: TranscriptionProgress) => void) => {
    const event = `transcription:${transcriptionId}:progress`
    on(event, callback)
    
    emit('join-transcription', { transcriptionId })
    
    return () => {
      off(event, callback)
      emit('leave-transcription', { transcriptionId })
    }
  }
  
  const subscribeToUserNotifications = (callback: (notification: any) => void) => {
    const event = 'user:notification'
    on(event, callback)
    
    return () => off(event, callback)
  }
  
  // Watch auth state
  watch(() => authStore.isAuthenticated, (isAuthenticated) => {
    if (isAuthenticated && autoConnect) {
      connect()
    } else {
      disconnect()
    }
  })
  
  // Lifecycle
  onMounted(() => {
    if (autoConnect && authStore.isAuthenticated) {
      connect()
    }
  })
  
  onUnmounted(() => {
    disconnect()
  })
  
  return {
    // State
    socket: socket.value,
    state,
    
    // Methods
    connect,
    disconnect,
    reconnect,
    on,
    off,
    emit,
    
    // Specific subscriptions
    subscribeToTranscriptionUpdates,
    subscribeToTranscriptionProgress,
    subscribeToUserNotifications
  }
}

// Global WebSocket instance
let globalWebSocket: ReturnType<typeof useWebSocket> | null = null

export function useGlobalWebSocket() {
  if (!globalWebSocket) {
    globalWebSocket = useWebSocket({ autoConnect: true })
  }
  return globalWebSocket
}

export default useWebSocket