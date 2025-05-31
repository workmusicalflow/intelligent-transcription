import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { useWebSocket } from './useWebSocket'
import { nextTick } from 'vue'
import { setActivePinia, createPinia } from 'pinia'

// Mock WebSocket
class MockWebSocket {
  url: string
  readyState: number = WebSocket.CONNECTING
  onopen: ((event: Event) => void) | null = null
  onclose: ((event: CloseEvent) => void) | null = null
  onerror: ((event: Event) => void) | null = null
  onmessage: ((event: MessageEvent) => void) | null = null
  
  constructor(url: string) {
    this.url = url
    setTimeout(() => {
      this.readyState = WebSocket.OPEN
      this.onopen?.(new Event('open'))
    }, 0)
  }
  
  send = vi.fn()
  close = vi.fn(() => {
    this.readyState = WebSocket.CLOSED
    this.onclose?.(new CloseEvent('close'))
  })
}

global.WebSocket = MockWebSocket as any

describe('useWebSocket', () => {
  let ws: ReturnType<typeof useWebSocket>
  
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.useFakeTimers()
  })
  
  afterEach(() => {
    ws?.disconnect()
    vi.clearAllTimers()
    vi.useRealTimers()
  })

  it('establishes connection on connect', async () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: false
    })

    expect(ws.isConnected.value).toBe(false)
    
    ws.connect()
    await vi.runAllTimersAsync()
    
    expect(ws.isConnected.value).toBe(true)
  })

  it('sends messages when connected', async () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: false
    })

    ws.connect()
    await vi.runAllTimersAsync()

    const message = { type: 'test', data: 'hello' }
    ws.send(message)

    const mockWs = ws.ws.value as any
    expect(mockWs.send).toHaveBeenCalledWith(JSON.stringify(message))
  })

  it('queues messages when not connected', () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: false
    })

    const message = { type: 'test', data: 'hello' }
    ws.send(message)

    const mockWs = ws.ws.value as any
    expect(mockWs?.send).not.toHaveBeenCalled()
  })

  it('handles incoming messages', async () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: false
    })

    const onMessage = vi.fn()
    ws.on('test', onMessage)

    ws.connect()
    await vi.runAllTimersAsync()

    const mockWs = ws.ws.value as any
    const messageData = { type: 'test', data: 'hello' }
    
    mockWs.onmessage?.(new MessageEvent('message', {
      data: JSON.stringify(messageData)
    }))

    expect(onMessage).toHaveBeenCalledWith(messageData)
  })

  it('handles connection errors', async () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: false
    })

    ws.connect()
    await vi.runAllTimersAsync()

    const mockWs = ws.ws.value as any
    mockWs.onerror?.(new Event('error'))

    expect(ws.error.value).toBeTruthy()
    expect(ws.isConnected.value).toBe(false)
  })

  it('attempts to reconnect on connection loss', async () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: true,
      maxReconnectAttempts: 3,
      reconnectInterval: 1000
    })

    ws.connect()
    await vi.runAllTimersAsync()

    expect(ws.isConnected.value).toBe(true)

    // Simulate connection loss
    const mockWs = ws.ws.value as any
    mockWs.close()

    expect(ws.isConnected.value).toBe(false)

    // Wait for reconnect attempt
    await vi.advanceTimersByTimeAsync(1000)
    await vi.runAllTimersAsync()

    expect(ws.isConnected.value).toBe(true)
  })

  it('stops reconnecting after max attempts', async () => {
    let connectAttempts = 0
    
    // Override MockWebSocket to fail connections
    const OriginalMockWebSocket = global.WebSocket
    global.WebSocket = class extends MockWebSocket {
      constructor(url: string) {
        super(url)
        connectAttempts++
        setTimeout(() => {
          this.readyState = WebSocket.CLOSED
          this.onclose?.(new CloseEvent('close'))
        }, 0)
      }
    } as any

    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: true,
      maxReconnectAttempts: 3,
      reconnectInterval: 100
    })

    ws.connect()

    // Run through all reconnect attempts
    for (let i = 0; i < 5; i++) {
      await vi.advanceTimersByTimeAsync(100)
      await vi.runAllTimersAsync()
    }

    expect(connectAttempts).toBe(4) // Initial + 3 reconnects
    expect(ws.isConnected.value).toBe(false)

    // Restore original
    global.WebSocket = OriginalMockWebSocket
  })

  it('unsubscribes event listeners', async () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: false
    })

    const onMessage = vi.fn()
    const unsubscribe = ws.on('test', onMessage)

    ws.connect()
    await vi.runAllTimersAsync()

    const mockWs = ws.ws.value as any
    const messageData = { type: 'test', data: 'hello' }
    
    // First message should be received
    mockWs.onmessage?.(new MessageEvent('message', {
      data: JSON.stringify(messageData)
    }))
    expect(onMessage).toHaveBeenCalledTimes(1)

    // Unsubscribe
    unsubscribe()

    // Second message should not be received
    mockWs.onmessage?.(new MessageEvent('message', {
      data: JSON.stringify(messageData)
    }))
    expect(onMessage).toHaveBeenCalledTimes(1)
  })

  it('handles subscription to specific events', async () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: false
    })

    const subscription = vi.fn()
    ws.subscribe('transcription:123', subscription)

    ws.connect()
    await vi.runAllTimersAsync()

    const mockWs = ws.ws.value as any
    
    // Should receive matching subscription
    mockWs.onmessage?.(new MessageEvent('message', {
      data: JSON.stringify({
        type: 'subscription',
        channel: 'transcription:123',
        data: { progress: 50 }
      })
    }))
    
    expect(subscription).toHaveBeenCalledWith({ progress: 50 })

    // Should not receive non-matching subscription
    mockWs.onmessage?.(new MessageEvent('message', {
      data: JSON.stringify({
        type: 'subscription',
        channel: 'transcription:456',
        data: { progress: 75 }
      })
    }))
    
    expect(subscription).toHaveBeenCalledTimes(1)
  })

  it('cleans up on disconnect', async () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: false
    })

    ws.connect()
    await vi.runAllTimersAsync()

    const mockWs = ws.ws.value as any
    expect(ws.isConnected.value).toBe(true)

    ws.disconnect()

    expect(mockWs.close).toHaveBeenCalled()
    expect(ws.isConnected.value).toBe(false)
    expect(ws.ws.value).toBeNull()
  })

  it('exports connection state', () => {
    ws = useWebSocket({
      url: 'ws://localhost:3000',
      reconnect: false
    })

    expect(ws.isConnected.value).toBe(false)
    expect(ws.isConnecting.value).toBe(false)
    expect(ws.error.value).toBeNull()
  })
})