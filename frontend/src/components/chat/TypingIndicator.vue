<template>
  <div class="flex gap-3 justify-start">
    <!-- Avatar IA -->
    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
      <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"></path>
      </svg>
    </div>

    <!-- Indicateur de saisie -->
    <div class="max-w-[75%] flex flex-col items-start">
      <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-4 py-3 rounded-2xl shadow-sm mr-8 relative">
        <!-- Contenu animé -->
        <div class="flex items-center space-x-1">
          <span class="text-sm text-gray-600 dark:text-gray-400">L'IA réfléchit</span>
          <div class="flex space-x-1 ml-2">
            <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
            <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
            <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
          </div>
        </div>

        <!-- Queue de la bulle -->
        <div class="absolute top-3 -left-1 w-3 h-3 bg-white dark:bg-gray-700 border-l border-b border-gray-200 dark:border-gray-600 transform rotate-45"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Composant purement présentationnel, pas de logique nécessaire
</script>

<style scoped>
/* Animation personnalisée pour les points */
@keyframes bounce-custom {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-8px);
  }
}

.animate-bounce {
  animation: bounce-custom 1.4s infinite ease-in-out;
}

/* Effet de pulsation subtile pour la bulle */
.animate-pulse-subtle {
  animation: pulse-subtle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-subtle {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}
</style>