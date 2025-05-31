<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
    <div class="max-w-2xl w-full text-center">
      <!-- 404 Animation -->
      <div class="relative mb-8">
        <div class="text-9xl font-bold text-blue-600 dark:text-blue-400 opacity-20 select-none">
          404
        </div>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="w-32 h-32 bg-blue-600 dark:bg-blue-400 rounded-full animate-bounce opacity-80">
            <div class="w-full h-full flex items-center justify-center text-white text-2xl font-bold">
              ?
            </div>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
        Page non trouvée
      </h1>
      <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
        Désolé, la page que vous recherchez n'existe pas ou a été déplacée.
      </p>

      <!-- Recovery Actions -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
        <button
          @click="goHome"
          class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
          Retour à l'accueil
        </button>
        <button
          @click="goBack"
          class="px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Page précédente
        </button>
      </div>

      <!-- Search Section -->
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 mb-8 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Rechercher du contenu
        </h3>
        <div class="flex gap-3">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher des transcriptions, conversations..."
            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @keydown.enter="performSearch"
          />
          <button
            @click="performSearch"
            :disabled="!searchQuery.trim()"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition-colors duration-200"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <router-link
          to="/transcriptions"
          class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 group"
        >
          <div class="text-blue-600 dark:text-blue-400 mb-2">
            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Transcriptions</h4>
          <p class="text-sm text-gray-600 dark:text-gray-400">Voir toutes vos transcriptions</p>
        </router-link>

        <router-link
          to="/chat"
          class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 group"
        >
          <div class="text-green-600 dark:text-green-400 mb-2">
            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Chat</h4>
          <p class="text-sm text-gray-600 dark:text-gray-400">Accéder au chat intelligent</p>
        </router-link>

        <router-link
          to="/dashboard"
          class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 group"
        >
          <div class="text-purple-600 dark:text-purple-400 mb-2">
            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Dashboard</h4>
          <p class="text-sm text-gray-600 dark:text-gray-400">Voir vos statistiques</p>
        </router-link>
      </div>

      <!-- Support Contact -->
      <div class="text-center">
        <p class="text-gray-600 dark:text-gray-400 mb-2">
          Besoin d'aide ? Contactez notre support
        </p>
        <a
          href="mailto:support@transcription-intelligente.com"
          class="text-blue-600 dark:text-blue-400 hover:underline font-medium"
        >
          support@transcription-intelligente.com
        </a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const searchQuery = ref('')

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

const performSearch = () => {
  if (searchQuery.value.trim()) {
    router.push({
      name: 'TranscriptionList',
      query: { search: searchQuery.value.trim() }
    })
  }
}
</script>

<script lang="ts">
export default {
  name: 'NotFound'
}
</script>