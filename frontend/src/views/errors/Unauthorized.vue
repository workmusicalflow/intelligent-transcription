<template>
  <div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
    <div class="max-w-lg w-full text-center">
      <!-- 401 Illustration -->
      <div class="relative mb-8">
        <div class="text-8xl font-bold text-red-600 dark:text-red-400 opacity-20 select-none">
          401
        </div>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="w-24 h-24 bg-red-600 dark:bg-red-400 rounded-full flex items-center justify-center animate-pulse">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
        Accès non autorisé
      </h1>
      <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
        {{ getErrorMessage() }}
      </p>

      <!-- Auth Actions -->
      <div class="space-y-4 mb-8">
        <template v-if="!isAuthenticated">
          <button
            @click="goToLogin"
            class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Se connecter
          </button>
          <button
            @click="goToRegister"
            class="w-full px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Créer un compte
          </button>
        </template>
        <template v-else>
          <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              <span class="text-yellow-800 dark:text-yellow-200 text-sm">
                Votre compte n'a pas les permissions nécessaires pour accéder à cette ressource.
              </span>
            </div>
          </div>
          <button
            @click="contactSupport"
            class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Contacter le support
          </button>
        </template>
      </div>

      <!-- Navigation Actions -->
      <div class="flex flex-col sm:flex-row gap-3 justify-center mb-8">
        <button
          @click="goHome"
          class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
          Accueil
        </button>
        <button
          @click="goBack"
          class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Retour
        </button>
      </div>

      <!-- Help Information -->
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
          Que puis-je faire ?
        </h3>
        <ul class="text-left space-y-2 text-gray-600 dark:text-gray-400">
          <li class="flex items-start gap-2">
            <svg class="w-4 h-4 mt-1 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Vérifiez que vous êtes connecté avec le bon compte</span>
          </li>
          <li class="flex items-start gap-2">
            <svg class="w-4 h-4 mt-1 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Contactez l'administrateur si vous devriez avoir accès</span>
          </li>
          <li class="flex items-start gap-2">
            <svg class="w-4 h-4 mt-1 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Créez un compte si vous n'en avez pas encore</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const isAuthenticated = computed(() => authStore.isAuthenticated)

const getErrorMessage = () => {
  if (!isAuthenticated.value) {
    return 'Vous devez vous connecter pour accéder à cette page.'
  }
  return 'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource.'
}

const goToLogin = () => {
  router.push({
    name: 'Login',
    query: { redirect: route.fullPath }
  })
}

const goToRegister = () => {
  router.push({
    name: 'Register',
    query: { redirect: route.fullPath }
  })
}

const goHome = () => {
  router.push('/')
}

const goBack = () => {
  if (window.history.length > 1) {
    router.go(-1)
  } else {
    router.push('/')
  }
}

const contactSupport = () => {
  window.location.href = 'mailto:support@transcription-intelligente.com?subject=Demande%20d\'accès&body=Bonjour,%0A%0AJe%20rencontre%20un%20problème%20d\'accès%20sur%20la%20page%20:%20' + encodeURIComponent(route.fullPath)
}
</script>

<script lang="ts">
export default {
  name: 'Unauthorized'
}
</script>