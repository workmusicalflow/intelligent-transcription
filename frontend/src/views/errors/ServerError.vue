<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
    <div class="max-w-lg w-full text-center">
      <!-- 500 Illustration -->
      <div class="relative mb-8">
        <div class="text-8xl font-bold text-gray-400 dark:text-gray-600 opacity-30 select-none">
          500
        </div>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="w-24 h-24 bg-red-500 dark:bg-red-400 rounded-full flex items-center justify-center animate-pulse">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
        Erreur serveur
      </h1>
      <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
        Une erreur inattendue s'est produite sur nos serveurs. Nous travaillons à résoudre le problème.
      </p>

      <!-- Error Details -->
      <div v-if="errorDetails" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-8 text-left">
        <h3 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
          Détails de l'erreur
        </h3>
        <p class="text-sm text-red-700 dark:text-red-300 font-mono">
          {{ errorDetails }}
        </p>
      </div>

      <!-- Retry Actions -->
      <div class="space-y-4 mb-8">
        <button
          @click="retryAction"
          :disabled="isRetrying"
          class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
        >
          <svg
            v-if="isRetrying"
            class="w-5 h-5 animate-spin"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
          <svg
            v-else
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          {{ isRetrying ? 'Nouvelle tentative...' : 'Réessayer' }}
        </button>

        <button
          @click="reloadPage"
          class="w-full px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Recharger la page
        </button>
      </div>

      <!-- Status Updates -->
      <div v-if="showStatusUpdates" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-8">
        <div class="flex items-center mb-2">
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-blue-800 dark:text-blue-200 text-sm font-semibold">
            Mises à jour du statut
          </span>
        </div>
        <p class="text-blue-700 dark:text-blue-300 text-sm">
          Nous surveillons activement cette situation. Les services devraient être rétablis sous peu.
        </p>
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

      <!-- Help Section -->
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
          Que faire en attendant ?
        </h3>
        <ul class="text-left space-y-2 text-gray-600 dark:text-gray-400">
          <li class="flex items-start gap-2">
            <svg class="w-4 h-4 mt-1 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Attendez quelques minutes et réessayez</span>
          </li>
          <li class="flex items-start gap-2">
            <svg class="w-4 h-4 mt-1 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Vérifiez votre connexion internet</span>
          </li>
          <li class="flex items-start gap-2">
            <svg class="w-4 h-4 mt-1 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Contactez le support si le problème persiste</span>
          </li>
        </ul>

        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">
            Support technique :
          </p>
          <a
            href="mailto:support@transcription-intelligente.com?subject=Erreur%20serveur&body=Bonjour,%0A%0AJe%20rencontre%20une%20erreur%20serveur%20(500)%20sur%20la%20page%20:%20{{ currentUrl }}"
            class="text-blue-600 dark:text-blue-400 hover:underline font-medium text-sm"
          >
            support@transcription-intelligente.com
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const isRetrying = ref(false)
const showStatusUpdates = ref(false)
const errorDetails = ref('')

const currentUrl = computed(() => route.fullPath)

onMounted(() => {
  // Afficher les mises à jour de statut après 5 secondes
  setTimeout(() => {
    showStatusUpdates.value = true
  }, 5000)

  // Récupérer les détails de l'erreur depuis les paramètres de la route ou le storage
  const storedError = sessionStorage.getItem('lastError')
  if (storedError) {
    try {
      const errorData = JSON.parse(storedError)
      errorDetails.value = errorData.message || errorData.error || ''
    } catch (e) {
      errorDetails.value = storedError
    }
  }
})

const retryAction = async () => {
  isRetrying.value = true
  
  try {
    // Simuler une tentative de reconnexion
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Effacer l'erreur stockée
    sessionStorage.removeItem('lastError')
    
    // Recharger la page ou retourner à la page précédente
    if (window.history.length > 1) {
      router.go(-1)
    } else {
      window.location.reload()
    }
  } catch (error) {
    console.error('Retry failed:', error)
  } finally {
    isRetrying.value = false
  }
}

const reloadPage = () => {
  sessionStorage.removeItem('lastError')
  window.location.reload()
}

const goHome = () => {
  sessionStorage.removeItem('lastError')
  router.push('/')
}

const goBack = () => {
  if (window.history.length > 1) {
    router.go(-1)
  } else {
    router.push('/')
  }
}
</script>

<script lang="ts">
export default {
  name: 'ServerError'
}
</script>