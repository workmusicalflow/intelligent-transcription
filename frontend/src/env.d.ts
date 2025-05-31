/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
  readonly VITE_GRAPHQL_ENDPOINT: string
  readonly VITE_WS_URL: string
  readonly VITE_APP_TITLE: string
  readonly VITE_APP_VERSION: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

// Global constants injected by Vite
declare const __APP_VERSION__: string
declare const __API_BASE_URL__: string
declare const __GRAPHQL_ENDPOINT__: string

// PWA
declare module 'virtual:pwa-register/vue' {
  export interface RegisterSWOptions {
    immediate?: boolean
    onNeedRefresh?: () => void
    onOfflineReady?: () => void
    onRegistered?: (registration: ServiceWorkerRegistration | undefined) => void
    onRegisterError?: (error: any) => void
  }

  export function useRegisterSW(options?: RegisterSWOptions): {
    needRefresh: import('vue').Ref<boolean>
    offlineReady: import('vue').Ref<boolean>
    updateServiceWorker: (reloadPage?: boolean) => Promise<void>
  }
}

// WebSocket types
interface BeforeInstallPromptEvent extends Event {
  readonly platforms: string[]
  readonly userChoice: Promise<{
    outcome: 'accepted' | 'dismissed'
    platform: string
  }>
  prompt(): Promise<void>
}

// Extend Window interface
declare global {
  interface Window {
    __APP_ENV__: {
      NODE_ENV: string
      API_BASE_URL: string
      GRAPHQL_ENDPOINT: string
      VERSION: string
    }
  }

  interface WindowEventMap {
    'beforeinstallprompt': BeforeInstallPromptEvent
  }
}

// Module declarations for missing types
declare module '@headlessui/vue'
declare module '@heroicons/vue/24/outline'
declare module '@heroicons/vue/24/solid'
declare module 'chart.js'
declare module 'vue-chartjs'

export {}