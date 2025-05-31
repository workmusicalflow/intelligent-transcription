<template>
  <div class="container-app section-padding">
    <!-- En-tête avec actions -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Conversations
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          Discutez avec l'IA sur vos transcriptions
        </p>
      </div>
      
      <div class="flex gap-3">
        <!-- Recherche -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher..."
            class="input-base pl-10 w-64"
            @input="debouncedSearch"
          >
          <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
        
        <!-- Bouton nouvelle conversation -->
        <Button
          variant="primary"
          size="md"
          @click="showCreateModal = true"
          :disabled="loading"
        >
          <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Nouvelle conversation
        </Button>
      </div>
    </div>

    <!-- Filtres rapides -->
    <div class="flex gap-2 mb-6">
      <button
        v-for="filter in filters"
        :key="filter.key"
        @click="activeFilter = filter.key"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          activeFilter === filter.key
            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700'
        ]"
      >
        {{ filter.label }}
        <span v-if="filter.count !== undefined" class="ml-2 px-2 py-1 text-xs bg-gray-200 dark:bg-gray-600 rounded-full">
          {{ filter.count }}
        </span>
      </button>
    </div>

    <!-- État de chargement -->
    <GlobalLoading v-if="loading && conversations.length === 0" class="py-12" />

    <!-- Liste des conversations -->
    <div v-else-if="conversations.length > 0" class="space-y-4">
      <ConversationCard
        v-for="conversation in filteredConversations"
        :key="conversation.id"
        :conversation="conversation"
        @click="openConversation(conversation.id)"
        @delete="deleteConversation(conversation.id)"
        @rename="renameConversation(conversation)"
        class="cursor-pointer hover:shadow-md transition-shadow"
      />
    </div>

    <!-- État vide -->
    <EmptyState
      v-else-if="!loading"
      title="Aucune conversation"
      description="Créez votre première conversation pour commencer à discuter avec l'IA"
      icon="chat-bubble-oval-left-ellipsis"
    >
      <Button
        variant="primary"
        size="md"
        @click="showCreateModal = true"
        class="mt-4"
      >
        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Créer une conversation
      </Button>
    </EmptyState>

    <!-- Pagination -->
    <div v-if="pagination.totalPages > 1" class="flex justify-center mt-8">
      <nav class="flex space-x-2">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="loadPage(page)"
          :disabled="loading"
          :class="[
            'px-3 py-2 text-sm rounded-md transition-colors',
            page === pagination.page
              ? 'bg-blue-600 text-white'
              : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
          ]"
        >
          {{ page }}
        </button>
      </nav>
    </div>

    <!-- Modal création conversation -->
    <CreateConversationModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="onConversationCreated"
    />

    <!-- Modal renommer conversation -->
    <RenameConversationModal
      v-if="showRenameModal && conversationToRename"
      :conversation="conversationToRename"
      @close="showRenameModal = false"
      @renamed="onConversationRenamed"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { debounce } from 'lodash-es'
import ChatAPI from '@/api/chat'
import type { Conversation, PaginatedResponse } from '@/types'
import Button from '@/components/ui/Button.vue'
import GlobalLoading from '@/components/ui/GlobalLoading.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ConversationCard from '@/components/chat/ConversationCard.vue'
import CreateConversationModal from '@/components/chat/CreateConversationModal.vue'
import RenameConversationModal from '@/components/chat/RenameConversationModal.vue'
import { useUIStore } from '@/stores/ui'

const router = useRouter()
const uiStore = useUIStore()

// State réactif
const loading = ref(false)
const conversations = ref<Conversation[]>([])
const searchQuery = ref('')
const activeFilter = ref('all')
const showCreateModal = ref(false)
const showRenameModal = ref(false)
const conversationToRename = ref<Conversation | null>(null)

// Pagination
const pagination = ref({
  page: 1,
  limit: 20,
  total: 0,
  totalPages: 0,
  hasNext: false,
  hasPrev: false
})

// Filtres disponibles
const filters = computed(() => {
  const all = conversations.value.length
  const withTranscription = conversations.value.filter(c => c.transcriptionId).length
  const general = all - withTranscription
  
  return [
    { key: 'all', label: 'Toutes', count: all },
    { key: 'transcription', label: 'Avec transcription', count: withTranscription },
    { key: 'general', label: 'Générales', count: general }
  ]
})

// Conversations filtrées
const filteredConversations = computed(() => {
  let filtered = conversations.value
  
  // Filtre par type
  if (activeFilter.value === 'transcription') {
    filtered = filtered.filter(c => c.transcriptionId)
  } else if (activeFilter.value === 'general') {
    filtered = filtered.filter(c => !c.transcriptionId)
  }
  
  // Filtre par recherche
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(c => 
      c.title.toLowerCase().includes(query) ||
      c.lastMessage?.content.toLowerCase().includes(query)
    )
  }
  
  return filtered
})

// Pages visibles pour pagination
const visiblePages = computed(() => {
  const total = pagination.value.totalPages
  const current = pagination.value.page
  const pages: number[] = []
  
  for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
    pages.push(i)
  }
  
  return pages
})

// Recherche avec debounce
const debouncedSearch = debounce(() => {
  loadConversations()
}, 300)

/**
 * Charger les conversations
 */
async function loadConversations() {
  try {
    loading.value = true
    
    const response = await ChatAPI.getConversations({
      page: pagination.value.page,
      limit: pagination.value.limit,
      search: searchQuery.value.trim() || undefined
    })
    
    if (response.success && response.data) {
      conversations.value = response.data.data
      pagination.value = response.data.pagination
    }
  } catch (error) {
    console.error('Erreur lors du chargement des conversations:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de charger les conversations'
    })
  } finally {
    loading.value = false
  }
}

/**
 * Charger une page spécifique
 */
async function loadPage(page: number) {
  pagination.value.page = page
  await loadConversations()
}

/**
 * Ouvrir une conversation
 */
function openConversation(id: string) {
  router.push({ name: 'ChatDetail', params: { id } })
}

/**
 * Supprimer une conversation
 */
async function deleteConversation(id: string) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette conversation ?')) {
    return
  }
  
  try {
    const response = await ChatAPI.deleteConversation(id)
    
    if (response.success) {
      conversations.value = conversations.value.filter(c => c.id !== id)
      uiStore.showNotification({
        type: 'success',
        title: 'Succès',
        message: 'Conversation supprimée'
      })
    }
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de supprimer la conversation'
    })
  }
}

/**
 * Renommer une conversation
 */
function renameConversation(conversation: Conversation) {
  conversationToRename.value = conversation
  showRenameModal.value = true
}

/**
 * Callback après création d'une conversation
 */
function onConversationCreated(conversation: Conversation) {
  conversations.value.unshift(conversation)
  showCreateModal.value = false
  
  uiStore.showNotification({
    type: 'success',
    title: 'Succès',
    message: 'Conversation créée'
  })
  
  // Ouvrir la nouvelle conversation
  openConversation(conversation.id)
}

/**
 * Callback après renommage d'une conversation
 */
function onConversationRenamed(updatedConversation: Conversation) {
  const index = conversations.value.findIndex(c => c.id === updatedConversation.id)
  if (index >= 0) {
    conversations.value[index] = updatedConversation
  }
  
  showRenameModal.value = false
  conversationToRename.value = null
  
  uiStore.showNotification({
    type: 'success',
    title: 'Succès',
    message: 'Conversation renommée'
  })
}

// Watchers
watch(activeFilter, () => {
  // Réinitialiser la pagination lors d'un changement de filtre
  pagination.value.page = 1
  // La recherche sera automatiquement relancée via le computed
})

// Lifecycle
onMounted(() => {
  loadConversations()
})
</script>

<script lang="ts">
export default {
  name: 'Chat'
}
</script>
