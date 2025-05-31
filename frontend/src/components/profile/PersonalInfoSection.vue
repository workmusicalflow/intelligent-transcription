<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
      Informations personnelles
    </h3>
    
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Nom -->
      <Input
        v-model="form.name"
        label="Nom complet"
        type="text"
        :disabled="loading"
        required
      />

      <!-- Email -->
      <Input
        v-model="form.email"
        label="Email"
        type="email"
        :disabled="loading"
        required
      />

      <!-- Téléphone -->
      <Input
        v-model="form.phone"
        label="Téléphone"
        type="tel"
        :disabled="loading"
      />

      <!-- Actions -->
      <div class="flex justify-end gap-3 pt-4">
        <Button type="submit" variant="primary" :loading="loading">
          Sauvegarder
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import type { User } from '@/types'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'

interface Props {
  user: User | null
  loading: boolean
}

interface Emits {
  (e: 'update', data: Partial<User>): void
  (e: 'upload-avatar', file: File): void
}

defineProps<Props>()
const emit = defineEmits<Emits>()

const form = reactive({
  name: '',
  email: '',
  phone: ''
})

function handleSubmit() {
  emit('update', form)
}
</script>