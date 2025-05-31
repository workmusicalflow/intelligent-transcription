<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Overlay -->
    <div 
      class="absolute inset-0 bg-black/50 backdrop-blur-sm"
      @click="$emit('close')"
    ></div>
    
    <!-- Modal -->
    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Diffusion générale
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Envoyer un message à tous les utilisateurs connectés
            </p>
          </div>
        </div>
        
        <button
          @click="$emit('close')"
          class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <!-- Body -->
      <form @submit.prevent="sendMessage" class="p-6 space-y-6">
        <!-- Type de message -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Type de message
          </label>
          <div class="grid grid-cols-3 gap-3">
            <button
              v-for="type in messageTypes"
              :key="type.value"
              type="button"
              @click="form.type = type.value"
              :class="[
                'flex flex-col items-center p-3 rounded-lg border-2 transition-colors',
                form.type === type.value
                  ? `${type.selectedClass} border-current`
                  : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
              ]"
            >
              <component :is="type.icon" :class="['w-5 h-5 mb-1', type.iconClass]" />
              <span :class="['text-xs font-medium', type.textClass]">
                {{ type.label }}
              </span>
            </button>
          </div>
        </div>
        
        <!-- Titre -->
        <div>
          <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Titre du message
          </label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            required
            placeholder="Entrez le titre de votre message..."
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
          />
        </div>
        
        <!-- Contenu -->
        <div>
          <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Contenu du message
          </label>
          <textarea
            id="content"
            v-model="form.content"
            rows="4"
            required
            placeholder="Rédigez votre message..."
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 resize-none"
          ></textarea>
          <div class="flex items-center justify-between mt-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ form.content.length }}/500 caractères
            </span>
            <span
              v-if="form.content.length > 500"
              class="text-xs text-red-500 dark:text-red-400"
            >
              Trop long
            </span>
          </div>
        </div>
        
        <!-- Options avancées -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
          <button
            type="button"
            @click="showAdvanced = !showAdvanced"
            class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
          >
            <svg 
              :class="['w-4 h-4 transition-transform', showAdvanced ? 'rotate-90' : '']"
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span>Options avancées</span>
          </button>
          
          <div v-if="showAdvanced" class="mt-4 space-y-4">
            <!-- Envoi programmé -->
            <div>
              <label class="flex items-center space-x-2">
                <input
                  v-model="form.scheduled"
                  type="checkbox"
                  class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-700"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  Programmer l'envoi
                </span>
              </label>
              
              <div v-if="form.scheduled" class="mt-2">
                <input
                  v-model="form.scheduledDate"
                  type="datetime-local"
                  :min="new Date().toISOString().slice(0, 16)"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                />
              </div>
            </div>
            
            <!-- Notification push -->
            <div>
              <label class="flex items-center space-x-2">
                <input
                  v-model="form.pushNotification"
                  type="checkbox"
                  class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-700"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  Envoyer une notification push
                </span>
              </label>
            </div>
            
            <!-- Email -->
            <div>
              <label class="flex items-center space-x-2">
                <input
                  v-model="form.sendEmail"
                  type="checkbox"
                  class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-700"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  Envoyer par email
                </span>
              </label>
            </div>
          </div>
        </div>
        
        <!-- Preview -->
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Aperçu du message
          </h4>
          <div 
            :class="[
              'p-3 rounded-lg border-l-4',
              previewClasses[form.type as keyof typeof previewClasses]
            ]"
          >
            <div class="flex items-start space-x-2">
              <component :is="getTypeIcon(form.type)" class="w-4 h-4 mt-0.5" />
              <div class="flex-1 min-w-0">
                <h5 class="font-medium text-sm mb-1">
                  {{ form.title || 'Titre du message' }}
                </h5>
                <p class="text-sm opacity-90">
                  {{ form.content || 'Contenu du message...' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </form>
      
      <!-- Footer -->
      <div class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ activeUsersCount }} utilisateurs connectés
        </div>
        
        <div class="flex items-center space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            Annuler
          </button>
          
          <button
            @click="sendMessage"
            :disabled="!isFormValid || sending"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center space-x-2"
          >
            <svg 
              v-if="sending"
              class="w-4 h-4 animate-spin" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>
              {{ sending ? 'Envoi...' : form.scheduled ? 'Programmer' : 'Envoyer' }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'

const emit = defineEmits<{
  close: []
  send: [message: { title: string; content: string; type: string; options?: any }]
}>()

const sending = ref(false)
const showAdvanced = ref(false)
const activeUsersCount = ref(1247) // Mock data

const form = reactive({
  type: 'info',
  title: '',
  content: '',
  scheduled: false,
  scheduledDate: '',
  pushNotification: true,
  sendEmail: false
})

// Types de messages disponibles
const messageTypes = [
  {
    value: 'info',
    label: 'Information',
    icon: () => ({
      template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
    }),
    iconClass: 'text-blue-600 dark:text-blue-400',
    textClass: 'text-blue-700 dark:text-blue-300',
    selectedClass: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
  },
  {
    value: 'warning',
    label: 'Avertissement',
    icon: () => ({
      template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>'
    }),
    iconClass: 'text-yellow-600 dark:text-yellow-400',
    textClass: 'text-yellow-700 dark:text-yellow-300',
    selectedClass: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300'
  },
  {
    value: 'success',
    label: 'Succès',
    icon: () => ({
      template: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
    }),
    iconClass: 'text-green-600 dark:text-green-400',
    textClass: 'text-green-700 dark:text-green-300',
    selectedClass: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'
  }
]

const previewClasses = {
  info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-400 text-blue-700 dark:text-blue-300',
  warning: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400 text-yellow-700 dark:text-yellow-300',
  success: 'bg-green-50 dark:bg-green-900/20 border-green-400 text-green-700 dark:text-green-300'
}

const isFormValid = computed(() => {
  return form.title.trim().length > 0 && 
         form.content.trim().length > 0 && 
         form.content.length <= 500 &&
         (!form.scheduled || form.scheduledDate)
})

function getTypeIcon(type: string) {
  const typeData = messageTypes.find(t => t.value === type)
  return typeData?.icon || messageTypes[0].icon
}

async function sendMessage() {
  if (!isFormValid.value || sending.value) return
  
  sending.value = true
  
  try {
    // Simulation d'envoi
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    const message = {
      title: form.title,
      content: form.content,
      type: form.type,
      options: {
        scheduled: form.scheduled,
        scheduledDate: form.scheduledDate,
        pushNotification: form.pushNotification,
        sendEmail: form.sendEmail
      }
    }
    
    emit('send', message)
  } catch (error) {
    console.error('Erreur lors de l\'envoi:', error)
  } finally {
    sending.value = false
  }
}
</script>

<script lang="ts">
export default {
  name: 'BroadcastModal'
}
</script>