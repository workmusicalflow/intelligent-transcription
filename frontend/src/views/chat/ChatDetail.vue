<template>
  <div class="h-full flex flex-col bg-gray-50 dark:bg-gray-900">
    <!-- En-tête conversation -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
      <div class="flex justify-between items-center">
        <!-- Informations conversation -->
        <div class="flex items-center gap-4">
          <!-- Bouton retour -->
          <button
            @click="goBack"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg transition-colors"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          
          <div v-if="conversation" class="flex-1 min-w-0">
            <!-- Titre conversation -->
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white truncate">
              {{ conversation.title }}
            </h1>
            
            <!-- Métadonnées -->
            <div class="flex items-center gap-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
              <span class="flex items-center">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                {{ conversation.messageCount }} messages
              </span>
              
              <span v-if="conversation.transcriptionId" class="flex items-center">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V3a1 1 0 011 1v14a1 1 0 01-1 1H6a1 1 0 01-1-1V4a1 1 0 011-1m0 0h2m8 0h-2"></path>
                </svg>
                Avec transcription
              </span>
              
              <span>Créée {{ formatRelativeDate(conversation.createdAt) }}</span>
            </div>
          </div>
        </div>
        
        <!-- Actions conversation -->
        <div class="flex items-center gap-2">
          <!-- Menu actions -->
          <div class="relative" ref="dropdownRef">
            <button
              @click="showMenu = !showMenu"
              class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg transition-colors"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
              </svg>
            </button>
            
            <!-- Menu dropdown -->
            <div
              v-show="showMenu"
              class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-10"
            >
              <button
                @click="exportConversation"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center"
              >
                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                </svg>
                Exporter
              </button>
              
              <button
                @click="clearMessages"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center"
              >
                <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Vider la conversation
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Zone de messages -->
    <div class="flex-1 flex flex-col min-h-0">
      <!-- Messages -->
      <div 
        ref="messagesContainer"
        class="flex-1 overflow-y-auto p-6 space-y-6"
        @scroll="handleScroll"
      >
        <!-- État de chargement initial -->
        <div v-if="loadingMessages && messages.length === 0" class="flex justify-center py-12">
          <LoadingSpinner size="lg" />
        </div>
        
        <!-- Messages -->
        <div v-else-if="messages.length > 0" class="space-y-6">
          <!-- Chargement messages plus anciens -->
          <div v-if="hasMoreMessages && !loadingMessages" class="flex justify-center">
            <button
              @click="loadMoreMessages"
              class="px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
            >
              Charger plus de messages
            </button>
          </div>
          
          <div v-if="loadingMessages && messages.length > 0" class="flex justify-center">
            <LoadingSpinner size="sm" />
          </div>
          
          <!-- Messages list -->
          <MessageBubble
            v-for="message in messages"
            :key="message.id"
            :message="message"
            :show-timestamp="shouldShowTimestamp(message)"
            class="animate-in slide-in-from-bottom-2 duration-300"
          />
        </div>
        
        <!-- État vide -->
        <div v-else-if="!loadingMessages" class="flex flex-col items-center justify-center py-12 text-center">
          <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            Démarrez la conversation
          </h3>
          <p class="text-gray-600 dark:text-gray-400 max-w-sm">
            Posez votre première question {{ conversation?.transcriptionId ? 'sur cette transcription' : 'à l\'IA' }} pour commencer la discussion.
          </p>
        </div>
        
        <!-- Indicateur d'écriture IA -->
        <TypingIndicator v-if="aiTyping" />
      </div>
      
      <!-- Zone de saisie -->
      <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4">
        <form @submit.prevent="sendMessage" class="flex gap-3">
          <!-- Champ message -->
          <div class="flex-1 relative">
            <textarea
              ref="messageInput"
              v-model="messageText"
              :disabled="sending || !conversation"
              placeholder="Tapez votre message..."
              rows="1"
              :maxlength="2000"
              class="w-full resize-none rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:focus:border-blue-400 dark:focus:ring-blue-400 transition-all"
              style="min-height: 44px; max-height: 120px;"
              @input="adjustTextareaHeight"
              @keydown="handleKeyDown"
            ></textarea>
            
            <!-- Compteur de caractères -->
            <div class="absolute bottom-1 right-2 text-xs text-gray-400">
              {{ messageText.length }}/2000
            </div>
          </div>
          
          <!-- Bouton envoyer -->
          <Button
            type="submit"
            variant="primary"
            size="md"
            :disabled="!messageText.trim() || sending || !conversation"
            :loading="sending"
            class="self-end px-6"
          >
            <svg v-if="!sending" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
            Envoyer
          </Button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import ChatAPI from '@/api/chat'
import type { Conversation, Message } from '@/types'
import Button from '@/components/ui/Button.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import MessageBubble from '@/components/chat/MessageBubble.vue'
import TypingIndicator from '@/components/chat/TypingIndicator.vue'
import { useUIStore } from '@/stores/ui'

const router = useRouter()
const route = useRoute()
const uiStore = useUIStore()

// Références DOM
const messagesContainer = ref<HTMLElement>()
const messageInput = ref<HTMLTextAreaElement>()
const dropdownRef = ref<HTMLElement>()

// État réactif
const conversation = ref<Conversation | null>(null)
const messages = ref<Message[]>([])
const messageText = ref('')
const loadingConversation = ref(false)
const loadingMessages = ref(false)
const sending = ref(false)
const aiTyping = ref(false)
const showMenu = ref(false)
const hasMoreMessages = ref(true)

// Pagination des messages
const messagesPage = ref(1)
const messagesLimit = 50

// ID de la conversation
const conversationId = computed(() => route.params.id as string)

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
  } else {
    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short'
    })
  }
}

/**
 * Déterminer si on doit afficher le timestamp pour un message
 */
function shouldShowTimestamp(message: Message): boolean {
  const messageIndex = messages.value.findIndex(m => m.id === message.id)
  if (messageIndex === 0) return true
  
  const prevMessage = messages.value[messageIndex - 1]
  if (!prevMessage) return true
  
  const currentTime = new Date(message.timestamp)
  const prevTime = new Date(prevMessage.timestamp)
  const diffMs = currentTime.getTime() - prevTime.getTime()
  
  // Afficher le timestamp si plus de 10 minutes entre les messages
  return diffMs > 10 * 60 * 1000
}

/**
 * Charger la conversation
 */
async function loadConversation() {
  try {
    loadingConversation.value = true
    
    const response = await ChatAPI.getConversation(conversationId.value)
    
    if (response.success && response.data) {
      conversation.value = response.data
    } else {
      uiStore.showNotification({
        type: 'error',
        title: 'Erreur',
        message: 'Conversation introuvable'
      })
      goBack()
    }
  } catch (error) {
    console.error('Erreur lors du chargement de la conversation:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de charger la conversation'
    })
    goBack()
  } finally {
    loadingConversation.value = false
  }
}

/**
 * Charger les messages
 */
async function loadMessages() {
  try {
    loadingMessages.value = true
    
    const response = await ChatAPI.getMessages(conversationId.value, {
      page: messagesPage.value,
      limit: messagesLimit
    })
    
    if (response.success && response.data) {
      const newMessages = response.data.data
      
      if (messagesPage.value === 1) {
        messages.value = newMessages
      } else {
        // Ajouter les messages plus anciens en début de liste
        messages.value = [...newMessages, ...messages.value]
      }
      
      hasMoreMessages.value = response.data.pagination.hasNext
    }
  } catch (error) {
    console.error('Erreur lors du chargement des messages:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de charger les messages'
    })
  } finally {
    loadingMessages.value = false
  }
}

/**
 * Charger plus de messages (anciens)
 */
async function loadMoreMessages() {
  if (hasMoreMessages.value && !loadingMessages.value) {
    messagesPage.value++
    await loadMessages()
  }
}

/**
 * Envoyer un message
 */
async function sendMessage() {
  const trimmedMessage = messageText.value.trim()
  if (!trimmedMessage || sending.value || !conversation.value) return
  
  try {
    sending.value = true
    aiTyping.value = true
    
    // Ajouter le message utilisateur immédiatement
    const userMessage: Message = {
      id: 'temp-' + Date.now(),
      conversationId: conversationId.value,
      content: trimmedMessage,
      role: 'user',
      timestamp: new Date().toISOString()
    }
    
    messages.value.push(userMessage)
    messageText.value = ''
    
    // Scroller vers le bas
    await nextTick()
    scrollToBottom()
    
    // Envoyer à l'API
    const response = await ChatAPI.sendMessage(conversationId.value, {
      message: trimmedMessage,
      context: conversation.value.transcriptionId ? {
        transcriptionId: conversation.value.transcriptionId
      } : undefined
    })
    
    if (response.success && response.data) {
      // Remplacer le message temporaire par le vrai message
      const messageIndex = messages.value.findIndex(m => m.id === userMessage.id)
      if (messageIndex >= 0) {
        messages.value[messageIndex] = {
          ...userMessage,
          id: response.data.message_id,
          timestamp: response.data.timestamp
        }
      }
      
      // Ajouter la réponse de l'IA
      const aiMessage: Message = {
        id: 'ai-' + Date.now(),
        conversationId: conversationId.value,
        content: response.data.response,
        role: 'assistant',
        timestamp: new Date().toISOString()
      }
      
      messages.value.push(aiMessage)
      
      // Mettre à jour le compteur de messages
      if (conversation.value) {
        conversation.value.messageCount += 2 // User + AI
        conversation.value.updatedAt = new Date().toISOString()
      }
      
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Erreur lors de l\'envoi du message:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible d\'envoyer le message'
    })
    
    // Supprimer le message temporaire en cas d'erreur
    const tempIndex = messages.value.findIndex(m => m.id.startsWith('temp-'))
    if (tempIndex >= 0) {
      messages.value.splice(tempIndex, 1)
    }
  } finally {
    sending.value = false
    aiTyping.value = false
  }
}

/**
 * Scroller vers le bas
 */
function scrollToBottom(smooth = true) {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTo({
      top: messagesContainer.value.scrollHeight,
      behavior: smooth ? 'smooth' : 'auto'
    })
  }
}

/**
 * Ajuster la hauteur du textarea
 */
function adjustTextareaHeight() {
  if (messageInput.value) {
    messageInput.value.style.height = 'auto'
    messageInput.value.style.height = Math.min(messageInput.value.scrollHeight, 120) + 'px'
  }
}

/**
 * Gérer les touches clavier
 */
function handleKeyDown(event: KeyboardEvent) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}

/**
 * Gérer le scroll pour charger plus de messages
 */
function handleScroll() {
  if (!messagesContainer.value || loadingMessages.value || !hasMoreMessages.value) return
  
  // Si on est en haut de la zone de messages, charger plus
  if (messagesContainer.value.scrollTop < 100) {
    loadMoreMessages()
  }
}

/**
 * Retourner à la liste des conversations
 */
function goBack() {
  router.push({ name: 'Chat' })
}

/**
 * Exporter la conversation
 */
async function exportConversation() {
  showMenu.value = false
  
  try {
    const response = await ChatAPI.exportConversation(conversationId.value, 'txt')
    
    if (response.success && response.data) {
      // Ouvrir le fichier dans un nouvel onglet ou déclencher le téléchargement
      window.open(response.data.file_url, '_blank')
      
      uiStore.showNotification({
        type: 'success',
        title: 'Succès',
        message: 'Conversation exportée'
      })
    }
  } catch (error) {
    console.error('Erreur lors de l\'export:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible d\'exporter la conversation'
    })
  }
}

/**
 * Vider les messages de la conversation
 */
async function clearMessages() {
  showMenu.value = false
  
  if (!confirm('Êtes-vous sûr de vouloir vider cette conversation ?')) {
    return
  }
  
  // TODO: Implémenter l'API pour vider les messages
  messages.value = []
  if (conversation.value) {
    conversation.value.messageCount = 0
  }
  
  uiStore.showNotification({
    type: 'success',
    title: 'Succès',
    message: 'Conversation vidée'
  })
}

/**
 * Fermer le menu en cliquant à l'extérieur
 */
function handleClickOutside(event: Event) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    showMenu.value = false
  }
}

// Watchers
watch(conversationId, (newId) => {
  if (newId) {
    messages.value = []
    messagesPage.value = 1
    hasMoreMessages.value = true
    loadConversation()
    loadMessages()
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  
  // Focus sur le champ de message
  nextTick(() => {
    messageInput.value?.focus()
  })
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<script lang="ts">
export default {
  name: 'ChatDetail'
}
</script>
