<template>
  <div 
    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-all duration-200"
    data-testid="conversation-item"
    @click="$emit('click')"
  >
    <!-- En-tête avec titre et actions -->
    <div class="flex justify-between items-start mb-3">
      <div class="flex-1 min-w-0">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
          {{ conversation.title }}
        </h3>
        
        <!-- Badge transcription -->
        <div class="flex items-center gap-2 mt-1">
          <span v-if="conversation.transcriptionId" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
            <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V3a1 1 0 011 1v14a1 1 0 01-1 1H6a1 1 0 01-1-1V4a1 1 0 011-1m0 0h2m8 0h-2"></path>
            </svg>
            Transcription
          </span>
          
          <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ messageCountText }}
          </span>
        </div>
      </div>
      
      <!-- Menu actions -->
      <div class="flex items-center gap-2">
        <!-- Statut dernière activité -->
        <div class="text-xs text-gray-500 dark:text-gray-400">
          {{ formatDate(conversation.updatedAt) }}
        </div>
        
        <!-- Menu dropdown -->
        <div class="relative" @click.stop>
          <button
            @click="showMenu = !showMenu"
            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded transition-colors"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
            </svg>
          </button>
          
          <!-- Menu dropdown -->
          <div
            v-show="showMenu"
            class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-10"
            @click.stop
          >
            <button
              @click="handleRename"
              class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center"
            >
              <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Renommer
            </button>
            
            <button
              @click="handleExport"
              class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center"
            >
              <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
              </svg>
              Exporter
            </button>
            
            <hr class="my-1 border-gray-200 dark:border-gray-600">
            
            <button
              @click="handleDelete"
              class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center"
              data-testid="delete-conversation"
            >
              <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
              Supprimer
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Dernier message -->
    <div v-if="conversation.lastMessage" class="mb-3">
      <div class="flex items-start gap-3">
        <!-- Avatar du rôle -->
        <div :class="[
          'flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium',
          conversation.lastMessage.role === 'user' 
            ? 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
            : 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
        ]">
          {{ conversation.lastMessage.role === 'user' ? 'U' : 'IA' }}
        </div>
        
        <!-- Contenu du message -->
        <div class="flex-1 min-w-0">
          <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
            {{ conversation.lastMessage.content }}
          </p>
        </div>
      </div>
    </div>
    
    <!-- Métadonnées de conversation -->
    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
      <div class="flex items-center gap-3">
        <span class="flex items-center">
          <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
          </svg>
          {{ conversation.messageCount }}
        </span>
        
        <span>Créée {{ formatRelativeDate(conversation.createdAt) }}</span>
      </div>
      
      <!-- Indicateur de statut -->
      <div class="flex items-center">
        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { Conversation } from '@/types'

interface Props {
  conversation: Conversation
}

interface Emits {
  (e: 'click'): void
  (e: 'delete'): void
  (e: 'rename'): void
  (e: 'export'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const showMenu = ref(false)

// Texte du nombre de messages
const messageCountText = computed(() => {
  const count = props.conversation.messageCount
  return count === 1 ? '1 message' : `${count} messages`
})

/**
 * Formater une date relative
 */
function formatRelativeDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) {
    return "aujourd'hui"
  } else if (diffDays === 1) {
    return "hier"
  } else if (diffDays < 7) {
    return `il y a ${diffDays} jours`
  } else if (diffDays < 30) {
    const weeks = Math.floor(diffDays / 7)
    return weeks === 1 ? 'il y a 1 semaine' : `il y a ${weeks} semaines`
  } else {
    const months = Math.floor(diffDays / 30)
    return months === 1 ? 'il y a 1 mois' : `il y a ${months} mois`
  }
}

/**
 * Formater une date complète
 */
function formatDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  
  if (diffHours < 24) {
    return date.toLocaleTimeString('fr-FR', { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  } else {
    return date.toLocaleDateString('fr-FR', { 
      day: 'numeric', 
      month: 'short' 
    })
  }
}

/**
 * Gérer le clic sur renommer
 */
function handleRename() {
  showMenu.value = false
  emit('rename')
}

/**
 * Gérer le clic sur exporter
 */
function handleExport() {
  showMenu.value = false
  emit('export')
}

/**
 * Gérer le clic sur supprimer
 */
function handleDelete() {
  showMenu.value = false
  emit('delete')
}

/**
 * Fermer le menu en cliquant à l'extérieur
 */
function handleClickOutside(event: Event) {
  const target = event.target as HTMLElement
  if (!target.closest('.relative')) {
    showMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>