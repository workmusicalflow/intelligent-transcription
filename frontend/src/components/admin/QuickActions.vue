<template>
  <div class="space-y-3">
    <!-- Action: Diffusion générale -->
    <button
      @click="$emit('broadcast')"
      :disabled="!!loading"
      class="w-full flex items-center space-x-3 p-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors group disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <div class="w-10 h-10 bg-blue-100 dark:bg-blue-800 rounded-lg flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-700 transition-colors">
        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
        </svg>
      </div>
      <div class="flex-1 text-left">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
          Diffusion générale
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Envoyer un message à tous les utilisateurs
        </p>
      </div>
      <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
      </svg>
    </button>

    <!-- Action: Mode maintenance -->
    <button
      @click="$emit('maintenance')"
      :disabled="loading === 'maintenance'"
      class="w-full flex items-center space-x-3 p-3 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition-colors group disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <div class="w-10 h-10 bg-orange-100 dark:bg-orange-800 rounded-lg flex items-center justify-center group-hover:bg-orange-200 dark:group-hover:bg-orange-700 transition-colors">
        <svg 
          v-if="loading === 'maintenance'"
          class="w-5 h-5 text-orange-600 dark:text-orange-300 animate-spin" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <svg 
          v-else
          class="w-5 h-5 text-orange-600 dark:text-orange-300" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
      </div>
      <div class="flex-1 text-left">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
          {{ loading === 'maintenance' ? 'Activation...' : 'Mode maintenance' }}
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Activer le mode maintenance du système
        </p>
      </div>
      <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
      </svg>
    </button>

    <!-- Action: Sauvegarde système -->
    <button
      @click="$emit('backup')"
      :disabled="loading === 'backup'"
      class="w-full flex items-center space-x-3 p-3 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors group disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <div class="w-10 h-10 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-700 transition-colors">
        <svg 
          v-if="loading === 'backup'"
          class="w-5 h-5 text-green-600 dark:text-green-300 animate-spin" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <svg 
          v-else
          class="w-5 h-5 text-green-600 dark:text-green-300" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
        </svg>
      </div>
      <div class="flex-1 text-left">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
          {{ loading === 'backup' ? 'Sauvegarde...' : 'Sauvegarde système' }}
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Créer une sauvegarde complète
        </p>
      </div>
      <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
      </svg>
    </button>

    <!-- Action: Vider le cache -->
    <button
      @click="$emit('clear-cache')"
      :disabled="loading === 'cache'"
      class="w-full flex items-center space-x-3 p-3 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors group disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <div class="w-10 h-10 bg-purple-100 dark:bg-purple-800 rounded-lg flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-700 transition-colors">
        <svg 
          v-if="loading === 'cache'"
          class="w-5 h-5 text-purple-600 dark:text-purple-300 animate-spin" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <svg 
          v-else
          class="w-5 h-5 text-purple-600 dark:text-purple-300" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
      </div>
      <div class="flex-1 text-left">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
          {{ loading === 'cache' ? 'Nettoyage...' : 'Vider le cache' }}
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Nettoyer le cache système
        </p>
      </div>
      <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
      </svg>
    </button>

    <!-- Divider -->
    <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

    <!-- Actions rapides supplémentaires -->
    <div class="grid grid-cols-2 gap-2">
      <!-- Logs système -->
      <router-link
        to="/admin/logs"
        class="flex flex-col items-center p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors group"
      >
        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-600 rounded-lg flex items-center justify-center group-hover:bg-gray-200 dark:group-hover:bg-gray-500 transition-colors mb-2">
          <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
        </div>
        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">
          Voir logs
        </span>
      </router-link>

      <!-- Utilisateurs -->
      <router-link
        to="/admin/users"
        class="flex flex-col items-center p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors group"
      >
        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-600 rounded-lg flex items-center justify-center group-hover:bg-gray-200 dark:group-hover:bg-gray-500 transition-colors mb-2">
          <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
        </div>
        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">
          Utilisateurs
        </span>
      </router-link>

      <!-- Paramètres -->
      <router-link
        to="/admin/settings"
        class="flex flex-col items-center p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors group"
      >
        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-600 rounded-lg flex items-center justify-center group-hover:bg-gray-200 dark:group-hover:bg-gray-500 transition-colors mb-2">
          <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
        </div>
        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">
          Paramètres
        </span>
      </router-link>

      <!-- Rapports -->
      <router-link
        to="/admin/reports"
        class="flex flex-col items-center p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors group"
      >
        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-600 rounded-lg flex items-center justify-center group-hover:bg-gray-200 dark:group-hover:bg-gray-500 transition-colors mb-2">
          <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
        </div>
        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">
          Rapports
        </span>
      </router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  loading?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  loading: null
})

const emit = defineEmits<{
  broadcast: []
  maintenance: []
  backup: []
  'clear-cache': []
}>()
</script>

<script lang="ts">
export default {
  name: 'QuickActions'
}
</script>