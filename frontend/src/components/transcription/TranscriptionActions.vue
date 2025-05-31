<template>
  <div class="relative">
    <button
      @click="isOpen = !isOpen"
      class="p-1 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    >
      <EllipsisVerticalIcon class="h-5 w-5" />
    </button>

    <!-- Dropdown menu -->
    <Transition name="dropdown">
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10"
      >
        <div class="py-1">
          <button
            v-if="transcription.status === 'completed'"
            @click="handleAction('download')"
            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <ArrowDownTrayIcon class="h-4 w-4 inline mr-2" />
            Télécharger
          </button>
          
          <button
            v-if="transcription.status === 'completed'"
            @click="handleAction('chat')"
            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <ChatBubbleLeftRightIcon class="h-4 w-4 inline mr-2" />
            Démarrer un chat
          </button>
          
          <button
            v-if="transcription.status === 'failed'"
            @click="handleAction('retry')"
            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <ArrowPathIcon class="h-4 w-4 inline mr-2" />
            Réessayer
          </button>
          
          <button
            @click="handleAction('duplicate')"
            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <DocumentDuplicateIcon class="h-4 w-4 inline mr-2" />
            Dupliquer
          </button>
          
          <hr class="my-1 border-gray-200 dark:border-gray-600" />
          
          <button
            @click="handleAction('delete')"
            class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <TrashIcon class="h-4 w-4 inline mr-2" />
            Supprimer
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {
  EllipsisVerticalIcon,
  ArrowDownTrayIcon,
  ChatBubbleLeftRightIcon,
  ArrowPathIcon,
  DocumentDuplicateIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'
import type { Transcription } from '@/types'

interface Props {
  transcription: Transcription
}

defineProps<Props>()

const emit = defineEmits<{
  action: [action: string]
}>()

const isOpen = ref(false)

const handleAction = (action: string) => {
  isOpen.value = false
  emit('action', action)
}
</script>

<script lang="ts">
export default {
  name: 'TranscriptionActions'
}
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>