<template>
  <ModalContainer @close="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
      <!-- En-tête -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Renommer la conversation
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Modifiez le titre de cette conversation
        </p>
      </div>

      <!-- Contenu -->
      <form @submit.prevent="renameConversation" class="px-6 py-4 space-y-4">
        <!-- Titre actuel -->
        <div>
          <label for="newTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Nouveau titre
          </label>
          <Input
            id="newTitle"
            v-model="newTitle"
            type="text"
            placeholder="Saisissez le nouveau titre..."
            :disabled="loading"
:maxlength="100"
            required
            class="w-full"
            @keydown.escape="$emit('close')"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ newTitle.length }}/100 caractères
          </p>
        </div>

        <!-- Aperçu -->
        <div v-if="newTitle.trim() && newTitle.trim() !== conversation.title" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
          <div class="text-xs text-blue-700 dark:text-blue-300 mb-1">Aperçu:</div>
          <div class="text-sm font-medium text-blue-900 dark:text-blue-100">
            {{ newTitle.trim() }}
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
            :disabled="!newTitle.trim() || newTitle.trim() === conversation.title"
          >
            Renommer
          </Button>
        </div>
      </form>
    </div>
  </ModalContainer>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import ChatAPI from '@/api/chat'
import type { Conversation } from '@/types'
import ModalContainer from '@/components/ui/ModalContainer.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import { useUIStore } from '@/stores/ui'

interface Props {
  conversation: Conversation
}

interface Emits {
  (e: 'close'): void
  (e: 'renamed', conversation: Conversation): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const uiStore = useUIStore()

// État réactif
const loading = ref(false)
const newTitle = ref(props.conversation.title)

/**
 * Renommer la conversation
 */
async function renameConversation() {
  const trimmedTitle = newTitle.value.trim()
  
  if (!trimmedTitle || trimmedTitle === props.conversation.title) {
    return
  }
  
  try {
    loading.value = true
    
    const response = await ChatAPI.updateConversationTitle(props.conversation.id, trimmedTitle)
    
    if (response.success) {
      // Créer l'objet conversation mis à jour
      const updatedConversation: Conversation = {
        ...props.conversation,
        title: trimmedTitle,
        updatedAt: new Date().toISOString()
      }
      
      emit('renamed', updatedConversation)
    }
  } catch (error) {
    console.error('Erreur lors du renommage:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de renommer la conversation'
    })
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  // Focus et sélection du texte
  setTimeout(() => {
    const input = document.getElementById('newTitle') as HTMLInputElement
    if (input) {
      input.focus()
      input.select()
    }
  }, 100)
})
</script>