<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
      Sécurité
    </h3>
    
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Mot de passe actuel -->
      <Input
        v-model="form.currentPassword"
        label="Mot de passe actuel"
        type="password"
        :disabled="loading"
        :error="errors.currentPassword"
        autocomplete="current-password"
      />

      <!-- Nouveau mot de passe -->
      <Input
        v-model="form.newPassword"
        label="Nouveau mot de passe"
        type="password"
        :disabled="loading"
        :error="errors.newPassword"
        autocomplete="new-password"
      />

      <!-- Confirmer le nouveau mot de passe -->
      <Input
        v-model="form.confirmPassword"
        label="Confirmer le nouveau mot de passe"
        type="password"
        :disabled="loading"
        :error="errors.confirmPassword"
        autocomplete="new-password"
      />

      <!-- Actions -->
      <div class="flex justify-end gap-3 pt-4">
        <Button type="submit" variant="primary" :loading="loading">
          Changer le mot de passe
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'

interface Props {
  loading: boolean
}

interface Emits {
  (e: 'change-password', data: { currentPassword: string; newPassword: string }): void
}

defineProps<Props>()
const emit = defineEmits<Emits>()

const form = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const errors = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

function validateForm() {
  errors.value = {
    currentPassword: '',
    newPassword: '',
    confirmPassword: ''
  }

  let isValid = true

  if (!form.currentPassword) {
    errors.value.currentPassword = 'Le mot de passe actuel est requis'
    isValid = false
  }

  if (!form.newPassword) {
    errors.value.newPassword = 'Le nouveau mot de passe est requis'
    isValid = false
  } else if (form.newPassword.length < 8) {
    errors.value.newPassword = 'Le mot de passe doit contenir au moins 8 caractères'
    isValid = false
  }

  if (form.newPassword !== form.confirmPassword) {
    errors.value.confirmPassword = 'Les mots de passe ne correspondent pas'
    isValid = false
  }

  return isValid
}

function handleSubmit() {
  if (!validateForm()) return

  emit('change-password', {
    currentPassword: form.currentPassword,
    newPassword: form.newPassword
  })

  // Reset form
  form.currentPassword = ''
  form.newPassword = ''
  form.confirmPassword = ''
}
</script>