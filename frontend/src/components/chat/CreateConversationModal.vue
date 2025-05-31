<template>
  <ModalContainer @close="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
      <!-- En-tête -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Nouvelle conversation
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Créez une conversation pour discuter avec l'IA
        </p>
      </div>

      <!-- Contenu -->
      <form @submit.prevent="createConversation" class="px-6 py-4 space-y-4">
        <!-- Titre -->
        <div>
          <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Titre de la conversation
          </label>
          <Input
            id="title"
            v-model="form.title"
            type="text"
            placeholder="Ex: Discussion sur ma présentation..."
            :disabled="loading"
:maxlength="100"
            required
            class="w-full"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ form.title.length }}/100 caractères
          </p>
        </div>

        <!-- Transcription associée -->
        <div>
          <label for="transcription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Transcription associée (optionnel)
          </label>
          
          <div class="relative">
            <select
              id="transcription"
              v-model="form.transcriptionId"
              :disabled="loading || loadingTranscriptions"
              class="input-base w-full appearance-none pr-10"
            >
              <option value="">Conversation générale</option>
              <option 
                v-for="transcription in transcriptions" 
                :key="transcription.id" 
                :value="transcription.id"
              >
                {{ transcription.audioFile.originalName }} 
                ({{ formatDate(transcription.createdAt) }})
              </option>
            </select>
            
            <!-- Icône select -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
              </svg>
            </div>
          </div>
          
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Choisissez une transcription pour un contexte spécifique
          </p>
        </div>

        <!-- Aperçu contexte -->
        <div v-if="selectedTranscription" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V3a1 1 0 011 1v14a1 1 0 01-1 1H6a1 1 0 01-1-1V4a1 1 0 011-1m0 0h2m8 0h-2"></path>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">
                {{ selectedTranscription.audioFile.originalName }}
              </h4>
              <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                Langue: {{ selectedTranscription.language.name }}
              </p>
              <p v-if="selectedTranscription.text" class="text-sm text-blue-800 dark:text-blue-200 mt-2 line-clamp-2">
                {{ selectedTranscription.text }}
              </p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4">
          <Button
            type="button"
            variant="secondary"
            @click="$emit('close')"
            :disabled="loading"
          >
            Annuler
          </Button>
          
          <Button
            type="submit"
            variant="primary"
            :loading="loading"
            :disabled="!form.title.trim()"
          >
            Créer la conversation
          </Button>
        </div>
      </form>
    </div>
  </ModalContainer>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import ChatAPI from '@/api/chat'
import { TranscriptionAPI } from '@/api/transcriptions'
import type { Conversation, Transcription } from '@/types'
import ModalContainer from '@/components/ui/ModalContainer.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import { useUIStore } from '@/stores/ui'

interface Emits {
  (e: 'close'): void
  (e: 'created', conversation: Conversation): void
}

const emit = defineEmits<Emits>()
const uiStore = useUIStore()

// État réactif
const loading = ref(false)
const loadingTranscriptions = ref(false)
const transcriptions = ref<Transcription[]>([])

// Formulaire
const form = ref({
  title: '',
  transcriptionId: ''
})

// Transcription sélectionnée
const selectedTranscription = computed(() => {
  if (!form.value.transcriptionId) return null
  return transcriptions.value.find(t => t.id === form.value.transcriptionId) || null
})

/**
 * Charger les transcriptions récentes
 */
async function loadTranscriptions() {
  try {
    loadingTranscriptions.value = true
    
    const response = await TranscriptionAPI.getTranscriptions({
      page: 1,
      limit: 20,
      sort: 'created_at',
      order: 'desc'
    })
    
    if (response.success && response.data) {
      transcriptions.value = response.data.data.filter(t => t.status === 'completed')
    }
  } catch (error) {
    console.error('Erreur lors du chargement des transcriptions:', error)
  } finally {
    loadingTranscriptions.value = false
  }
}

/**
 * Formater une date
 */
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

/**
 * Créer la conversation
 */
async function createConversation() {
  if (!form.value.title.trim()) return
  
  try {
    loading.value = true
    
    const response = await ChatAPI.createConversation({
      title: form.value.title.trim(),
      transcriptionId: form.value.transcriptionId || undefined
    })
    
    if (response.success && response.data) {
      // Créer l'objet conversation pour l'emit
      const newConversation: Conversation = {
        id: response.data.conversation_id,
        title: form.value.title.trim(),
        transcriptionId: form.value.transcriptionId || undefined,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
        messageCount: 0
      }
      
      emit('created', newConversation)
    }
  } catch (error) {
    console.error('Erreur lors de la création:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de créer la conversation'
    })
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadTranscriptions()
  
  // Focus sur le champ titre
  setTimeout(() => {
    const titleInput = document.getElementById('title')
    titleInput?.focus()
  }, 100)
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