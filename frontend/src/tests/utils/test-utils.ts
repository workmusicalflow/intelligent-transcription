import { mount, VueWrapper } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory, Router } from 'vue-router'
import { Component } from 'vue'
// We'll create routes inline to avoid circular dependencies

export interface TestingOptions {
  props?: Record<string, any>
  slots?: Record<string, any>
  global?: {
    stubs?: Record<string, any>
    mocks?: Record<string, any>
    provide?: Record<string, any>
    plugins?: any[]
  }
  attachTo?: HTMLElement | string
  data?: () => Record<string, any>
}

// Create a fresh pinia instance for testing
export function createTestingPinia() {
  const pinia = createPinia()
  setActivePinia(pinia)
  return pinia
}

// Create a router instance for testing
export function createTestingRouter(): Router {
  return createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
      { path: '/dashboard', name: 'dashboard', component: { template: '<div>Dashboard</div>' } },
      { path: '/login', name: 'login', component: { template: '<div>Login</div>' } }
    ]
  })
}

// Mount component with all necessary plugins
export function mountWithPlugins(
  component: Component,
  options: TestingOptions = {}
): VueWrapper {
  const pinia = createTestingPinia()
  const router = createTestingRouter()

  return mount(component, {
    ...options,
    global: {
      plugins: [pinia, router, ...(options.global?.plugins || [])],
      stubs: {
        teleport: true,
        ...options.global?.stubs
      },
      mocks: {
        ...options.global?.mocks
      },
      provide: {
        ...options.global?.provide
      }
    }
  })
}

// Wait for async operations
export async function flushPromises(): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, 0))
}

// Wait for specific condition
export async function waitFor(
  condition: () => boolean,
  timeout = 1000,
  interval = 10
): Promise<void> {
  const start = Date.now()
  
  return new Promise((resolve, reject) => {
    const check = () => {
      if (condition()) {
        resolve()
      } else if (Date.now() - start > timeout) {
        reject(new Error('Timeout waiting for condition'))
      } else {
        setTimeout(check, interval)
      }
    }
    check()
  })
}

// Mock API response
export function mockApiResponse<T>(data: T, delay = 0): Promise<T> {
  return new Promise(resolve => {
    setTimeout(() => resolve(data), delay)
  })
}

// Create mock file
export function createMockFile(
  name = 'test.txt',
  size = 1024,
  type = 'text/plain'
): File {
  const content = new Array(size).fill('a').join('')
  return new File([content], name, { type })
}

// Test data factories
export const factories = {
  user: (overrides = {}) => ({
    id: '1',
    email: 'test@example.com',
    name: 'Test User',
    role: 'user' as const,
    createdAt: new Date().toISOString(),
    preferences: {
      theme: 'light' as const,
      language: 'fr',
      notifications: {
        email: true,
        push: true,
        transcriptionComplete: true,
        transcriptionFailed: true
      },
      defaultTranscriptionLanguage: 'fr'
    },
    ...overrides
  }),

  transcription: (overrides = {}) => ({
    id: '1',
    status: 'completed' as const,
    language: { code: 'fr', name: 'Français' },
    text: 'Transcription text',
    cost: { amount: 1.5, currency: 'USD', formatted: '$1.50' },
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
    audioFile: {
      path: '/audio/test.mp3',
      originalName: 'test.mp3',
      mimeType: 'audio/mp3',
      size: 1024000,
      duration: 180
    },
    userId: '1',
    ...overrides
  }),

  conversation: (overrides = {}) => ({
    id: '1',
    title: 'Test Conversation',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
    messageCount: 2,
    ...overrides
  }),

  message: (overrides = {}) => ({
    id: '1',
    conversationId: '1',
    content: 'Test message',
    role: 'user' as const,
    timestamp: new Date().toISOString(),
    ...overrides
  })
}

// Custom matchers
export const customMatchers = {
  toBeVisible(element: HTMLElement) {
    const pass = element && 
      !element.hidden &&
      element.style.display !== 'none' &&
      element.style.visibility !== 'hidden' &&
      element.style.opacity !== '0'

    return {
      pass,
      message: () => `expected element to ${pass ? 'not ' : ''}be visible`
    }
  },

  toHaveClass(element: HTMLElement, className: string) {
    const pass = element && element.classList.contains(className)
    
    return {
      pass,
      message: () => `expected element to ${pass ? 'not ' : ''}have class "${className}"`
    }
  }
}