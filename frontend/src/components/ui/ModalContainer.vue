<template>
  <div>
    <TransitionGroup name="modal">
      <div
        v-for="modal in uiStore.modals"
        :key="modal.id"
        class="fixed inset-0 z-50 overflow-y-auto"
      >
        <!-- Backdrop -->
        <div 
          class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
          @click="!modal.persistent && uiStore.closeModal(modal.id)"
        />
        
        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
          <component 
            :is="getModalComponent(modal.component)"
            v-bind="modal.props || {}"
            @close="() => uiStore.closeModal(modal.id)"
          />
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { defineAsyncComponent, type Component } from 'vue'
import { useUIStore } from '@stores/ui'

const uiStore = useUIStore()

// Modal components registry
const modalComponents: Record<string, Component> = {
  ConfirmationModal: defineAsyncComponent(() => import('./ConfirmationModal.vue')),
  // Add other modal components here
}

const getModalComponent = (componentName: string): Component => {
  return modalComponents[componentName] || 'div'
}
</script>

<script lang="ts">
export default {
  name: 'ModalContainer'
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .fixed {
  transform: scale(0.95);
}

.modal-leave-to .fixed {
  transform: scale(0.95);
}
</style>