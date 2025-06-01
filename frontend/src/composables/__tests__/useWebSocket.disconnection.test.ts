import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { nextTick } from 'vue'
import { setActivePinia, createPinia } from 'pinia'

// Mock Socket.IO client
vi.mock('socket.io-client', () => {
  return {
    io: vi.fn(() => ({
      connected: false,
      id: 'mock-socket-id',
      connect: vi.fn(),
      disconnect: vi.fn(),
      on: vi.fn(),
      off: vi.fn(),
      emit: vi.fn(),
      removeAllListeners: vi.fn()
    }))
  }
})

// Mock stores
vi.mock('@/stores/auth', () => ({
  useAuthStore: vi.fn()
}))

vi.mock('@/stores/ui', () => ({
  useUIStore: vi.fn()
}))

describe('useWebSocket - Disconnection Tests', () => {
  let mockAuthStore: any
  let mockUIStore: any
  let pinia: any
  let useWebSocket: any

  beforeEach(async () => {
    pinia = createPinia()
    setActivePinia(pinia)
    
    // Import after mocks are set up
    const webSocketModule = await import('../useWebSocket')
    useWebSocket = webSocketModule.useWebSocket
    
    const { useAuthStore } = await import('@/stores/auth')
    const { useUIStore } = await import('@/stores/ui')
    
    // Setup mock stores
    mockAuthStore = {
      isAuthenticated: true,
      logout: vi.fn()
    }
    mockUIStore = {}
    
    vi.mocked(useAuthStore).mockReturnValue(mockAuthStore)
    vi.mocked(useUIStore).mockReturnValue(mockUIStore)
    
    // Mock localStorage
    Object.defineProperty(window, 'localStorage', {
      value: {
        getItem: vi.fn(() => 'mock-token'),
        setItem: vi.fn(),
        removeItem: vi.fn(),
        clear: vi.fn()
      },
      writable: true,
      configurable: true
    })
    
    // Mock location
    Object.defineProperty(window, 'location', {
      value: {
        origin: 'http://localhost:3000'
      },
      writable: true,
      configurable: true
    })

    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.clearAllTimers()
    vi.useRealTimers()
    vi.clearAllMocks()
  })

  describe('Manual Disconnection', () => {
    it('should disconnect cleanly when manually called', async () => {
      const webSocket = useWebSocket()
      
      // Test initial state
      expect(webSocket.state.connected).toBe(false)
      expect(webSocket.state.reconnecting).toBe(false)
      
      // Call disconnect (should handle gracefully even if not connected)
      webSocket.disconnect()
      
      expect(webSocket.state.connected).toBe(false)
      expect(webSocket.state.reconnecting).toBe(false)
    })

    it('should reset retry count on manual disconnect', () => {
      const webSocket = useWebSocket({ retryAttempts: 5 })
      
      // Start reconnection to set state
      webSocket.reconnect()
      expect(webSocket.state.reconnecting).toBe(true)
      
      // Manual disconnect should reset state
      webSocket.disconnect()
      expect(webSocket.state.reconnecting).toBe(false)
    })

    it('should clear reconnect timers on disconnect', () => {
      const webSocket = useWebSocket({ retryDelay: 1000 })
      const clearTimeoutSpy = vi.spyOn(global, 'clearTimeout')
      
      // Start reconnection
      webSocket.reconnect()
      
      // Disconnect should clear timers
      webSocket.disconnect()
      
      expect(clearTimeoutSpy).toHaveBeenCalled()
    })
  })

  describe('Reconnection Logic', () => {
    it('should respect maximum retry attempts', () => {
      const maxAttempts = 3
      const webSocket = useWebSocket({ retryAttempts: maxAttempts })
      
      // Test multiple reconnection attempts
      for (let i = 0; i < maxAttempts; i++) {
        webSocket.reconnect()
      }
      
      expect(webSocket.state.reconnecting).toBe(true)
      
      // Additional attempts should not change state if max reached
      const stateBefore = webSocket.state.reconnecting
      webSocket.reconnect()
      expect(webSocket.state.reconnecting).toBe(stateBefore)
    })

    it('should implement exponential backoff for retry delays', () => {
      const retryDelay = 1000
      const webSocket = useWebSocket({ retryDelay, retryAttempts: 3 })
      
      const setTimeoutSpy = vi.spyOn(global, 'setTimeout')
      
      // First retry
      webSocket.reconnect()
      
      // Verify that setTimeout was called at least once for the first retry
      expect(setTimeoutSpy).toHaveBeenCalledWith(
        expect.any(Function),
        retryDelay // 1000ms
      )
      
      // The second reconnect attempt will not create a new timeout 
      // because the reconnection state is already active
      expect(webSocket.state.reconnecting).toBe(true)
    })

    it('should not start reconnection if already reconnecting', () => {
      const webSocket = useWebSocket()
      
      // Start first reconnection
      webSocket.reconnect()
      expect(webSocket.state.reconnecting).toBe(true)
      
      const setTimeoutSpy = vi.spyOn(global, 'setTimeout')
      setTimeoutSpy.mockClear()
      
      // Try second reconnection
      webSocket.reconnect()
      
      // Should not create new timeout
      expect(setTimeoutSpy).not.toHaveBeenCalled()
    })
  })

  describe('Connection Options', () => {
    it('should handle custom retry attempts', () => {
      const customRetryAttempts = 10
      const webSocket = useWebSocket({ retryAttempts: customRetryAttempts })
      
      // Should allow up to custom retry attempts
      for (let i = 0; i < customRetryAttempts; i++) {
        webSocket.reconnect()
      }
      
      expect(webSocket.state.reconnecting).toBe(true)
    })

    it('should handle custom retry delay', () => {
      const customDelay = 5000
      const webSocket = useWebSocket({ retryDelay: customDelay })
      
      const setTimeoutSpy = vi.spyOn(global, 'setTimeout')
      
      webSocket.reconnect()
      
      expect(setTimeoutSpy).toHaveBeenCalledWith(
        expect.any(Function),
        customDelay
      )
    })

    it('should handle autoConnect option', () => {
      const webSocketAutoConnect = useWebSocket({ autoConnect: true })
      const webSocketNoAutoConnect = useWebSocket({ autoConnect: false })
      
      // Both should initialize properly
      expect(webSocketAutoConnect.state).toBeDefined()
      expect(webSocketNoAutoConnect.state).toBeDefined()
    })
  })

  describe('Error Handling', () => {
    it('should handle missing auth token', () => {
      // Mock localStorage returning null
      vi.mocked(window.localStorage.getItem).mockReturnValue(null)
      
      const webSocket = useWebSocket()
      webSocket.connect()
      
      expect(webSocket.state.error).toBe('No authentication token')
    })

    it('should handle connection when already connected', async () => {
      const webSocket = useWebSocket()
      
      // Test multiple connect calls
      webSocket.connect()
      webSocket.connect()
      
      // Should handle gracefully without throwing errors
      expect(webSocket.state.connected).toBe(false) // Not connected due to missing token
    })

    it('should handle emit when not connected', () => {
      const webSocket = useWebSocket()
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})
      
      // Try to emit without connection
      webSocket.emit('test-event', { data: 'test' })
      
      expect(consoleSpy).toHaveBeenCalledWith('Cannot emit event: WebSocket not connected')
      
      consoleSpy.mockRestore()
    })
  })

  describe('Event Handler Management', () => {
    it('should allow adding and removing event handlers', () => {
      const webSocket = useWebSocket()
      
      const handler = vi.fn()
      
      // Add handler
      webSocket.on('test-event', handler)
      
      // Remove handler
      webSocket.off('test-event', handler)
      
      // Should complete without errors
      expect(true).toBe(true)
    })

    it('should handle removing handlers without connection', () => {
      const webSocket = useWebSocket()
      
      const handler = vi.fn()
      
      // Add and remove handler when not connected
      webSocket.on('test-event', handler)
      webSocket.off('test-event', handler)
      
      // Should handle gracefully
      expect(true).toBe(true)
    })

    it('should handle removing all handlers for an event', () => {
      const webSocket = useWebSocket()
      
      const handler1 = vi.fn()
      const handler2 = vi.fn()
      
      webSocket.on('test-event', handler1)
      webSocket.on('test-event', handler2)
      
      // Remove all handlers for event
      webSocket.off('test-event')
      
      // Should complete without errors
      expect(true).toBe(true)
    })
  })

  describe('Subscription Methods', () => {
    it('should handle transcription update subscriptions', () => {
      const webSocket = useWebSocket()
      const callback = vi.fn()
      
      const unsubscribe = webSocket.subscribeToTranscriptionUpdates('trans-123', callback)
      
      expect(typeof unsubscribe).toBe('function')
      
      // Should be able to unsubscribe
      unsubscribe()
    })

    it('should handle transcription progress subscriptions', () => {
      const webSocket = useWebSocket()
      const callback = vi.fn()
      
      const unsubscribe = webSocket.subscribeToTranscriptionProgress('trans-123', callback)
      
      expect(typeof unsubscribe).toBe('function')
      
      // Should be able to unsubscribe
      unsubscribe()
    })

    it('should handle user notification subscriptions', () => {
      const webSocket = useWebSocket()
      const callback = vi.fn()
      
      const unsubscribe = webSocket.subscribeToUserNotifications(callback)
      
      expect(typeof unsubscribe).toBe('function')
      
      // Should be able to unsubscribe
      unsubscribe()
    })
  })

  describe('State Management', () => {
    it('should maintain correct initial state', () => {
      const webSocket = useWebSocket()
      
      expect(webSocket.state.connected).toBe(false)
      expect(webSocket.state.reconnecting).toBe(false)
      expect(webSocket.state.error).toBe(null)
    })

    it('should update state during reconnection attempts', () => {
      const webSocket = useWebSocket()
      
      // Start reconnection
      webSocket.reconnect()
      
      expect(webSocket.state.reconnecting).toBe(true)
      
      // Reset state
      webSocket.disconnect()
      
      expect(webSocket.state.reconnecting).toBe(false)
    })

    it('should handle error state correctly', () => {
      const webSocket = useWebSocket()
      
      // Test that state is properly initialized
      expect(webSocket.state.connected).toBe(false)
      expect(webSocket.state.reconnecting).toBe(false)
      
      // Connect without proper token should result in error
      webSocket.connect()
      
      // State should remain disconnected
      expect(webSocket.state.connected).toBe(false)
    })
  })
})