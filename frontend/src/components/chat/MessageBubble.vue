<template>
  <div :class="[
    'flex gap-3',
    message.role === 'user' ? 'justify-end' : 'justify-start'
  ]">
    <!-- Avatar (côté gauche pour l'IA) -->
    <div 
      v-if="message.role === 'assistant'" 
      class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center"
    >
      <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"></path>
      </svg>
    </div>

    <!-- Contenu du message -->
    <div :class="[
      'max-w-[75%] flex flex-col',
      message.role === 'user' ? 'items-end' : 'items-start'
    ]">
      <!-- Timestamp -->
      <div v-if="showTimestamp" class="text-xs text-gray-500 dark:text-gray-400 mb-1 px-1">
        {{ formatTimestamp(message.timestamp) }}
      </div>

      <!-- Bulle de message -->
      <div :class="[
        'relative px-4 py-3 rounded-2xl shadow-sm',
        message.role === 'user' 
          ? 'bg-blue-600 text-white ml-8' 
          : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600 mr-8'
      ]">
        <!-- Contenu du message -->
        <div class="prose prose-sm max-w-none">
          <MessageContent :content="message.content" :role="message.role" />
        </div>

        <!-- Queue de la bulle -->
        <div v-if="message.role === 'user'" 
             class="absolute top-3 -right-1 w-3 h-3 bg-blue-600 transform rotate-45"></div>
        <div v-else 
             class="absolute top-3 -left-1 w-3 h-3 bg-white dark:bg-gray-700 border-l border-b border-gray-200 dark:border-gray-600 transform rotate-45"></div>
      </div>

      <!-- Métadonnées du message -->
      <div v-if="showMetadata" class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
        <!-- Heure du message -->
        <span>{{ formatTime(message.timestamp) }}</span>
        
        <!-- Statut du message (pour les messages utilisateur) -->
        <div v-if="message.role === 'user'" class="flex items-center">
          <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
          </svg>
        </div>
        
        <!-- Indicateur de confiance IA (si disponible) -->
        <div v-if="message.role === 'assistant' && message.metadata?.confidence" 
             class="flex items-center">
          <span class="text-xs">{{ Math.round(message.metadata.confidence * 100) }}%</span>
        </div>
      </div>
    </div>

    <!-- Avatar (côté droit pour l'utilisateur) -->
    <div 
      v-if="message.role === 'user'" 
      class="flex-shrink-0 w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center"
    >
      <svg class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
      </svg>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Message } from '@/types'
import MessageContent from './MessageContent.vue'

interface Props {
  message: Message
  showTimestamp?: boolean
  showMetadata?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showTimestamp: false,
  showMetadata: true
})

/**
 * Formater le timestamp complet
 */
function formatTimestamp(timestamp: string): string {
  const date = new Date(timestamp)
  const now = new Date()
  
  const isToday = date.toDateString() === now.toDateString()
  const isYesterday = new Date(now.getTime() - 86400000).toDateString() === date.toDateString()
  
  if (isToday) {
    return `Aujourd'hui à ${formatTime(timestamp)}`
  } else if (isYesterday) {
    return `Hier à ${formatTime(timestamp)}`
  } else {
    return date.toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

/**
 * Formater l'heure seulement
 */
function formatTime(timestamp: string): string {
  return new Date(timestamp).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
/* Animations d'entrée */
@keyframes slide-in-from-bottom {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-in {
  animation: slide-in-from-bottom 0.3s ease-out;
}

/* Styles pour le contenu prose */
.prose {
  color: inherit;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
  color: inherit;
  margin-top: 0.5em;
  margin-bottom: 0.25em;
}

.prose p {
  margin-top: 0;
  margin-bottom: 0.5em;
}

.prose p:last-child {
  margin-bottom: 0;
}

.prose ul, .prose ol {
  margin-top: 0.25em;
  margin-bottom: 0.25em;
  padding-left: 1.25em;
}

.prose li {
  margin-top: 0.125em;
  margin-bottom: 0.125em;
}

.prose code {
  background-color: rgba(0, 0, 0, 0.1);
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875em;
}

.dark .prose code {
  background-color: rgba(255, 255, 255, 0.1);
}

.prose pre {
  background-color: rgba(0, 0, 0, 0.05);
  border-radius: 0.375rem;
  padding: 0.75rem;
  overflow-x: auto;
  margin-top: 0.5em;
  margin-bottom: 0.5em;
}

.dark .prose pre {
  background-color: rgba(255, 255, 255, 0.05);
}

.prose blockquote {
  border-left: 4px solid currentColor;
  padding-left: 1rem;
  margin-left: 0;
  margin-right: 0;
  margin-top: 0.5em;
  margin-bottom: 0.5em;
  opacity: 0.8;
}
</style>