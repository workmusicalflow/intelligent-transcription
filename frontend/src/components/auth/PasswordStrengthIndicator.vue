<template>
  <div class="space-y-2">
    <!-- Barre de progression -->
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
      <div
        :class="[
          'h-2 rounded-full transition-all duration-300',
          strengthColor
        ]"
        :style="{ width: `${strengthPercentage}%` }"
      ></div>
    </div>

    <!-- Indicateur textuel -->
    <div class="flex justify-between items-center text-xs">
      <span :class="strengthTextColor">
        {{ strengthText }}
      </span>
      <span class="text-gray-500 dark:text-gray-400">
        {{ password.length }}/50
      </span>
    </div>

    <!-- Critères de validation -->
    <div class="space-y-1">
      <div class="flex items-center text-xs">
        <CheckIcon :valid="hasMinLength" />
        <span :class="hasMinLength ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
          Au moins 8 caractères
        </span>
      </div>
      
      <div class="flex items-center text-xs">
        <CheckIcon :valid="hasLowercase" />
        <span :class="hasLowercase ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
          Une lettre minuscule
        </span>
      </div>
      
      <div class="flex items-center text-xs">
        <CheckIcon :valid="hasUppercase" />
        <span :class="hasUppercase ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
          Une lettre majuscule
        </span>
      </div>
      
      <div class="flex items-center text-xs">
        <CheckIcon :valid="hasNumber" />
        <span :class="hasNumber ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
          Un chiffre
        </span>
      </div>
      
      <div class="flex items-center text-xs">
        <CheckIcon :valid="hasSpecialChar" />
        <span :class="hasSpecialChar ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
          Un caractère spécial
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  password: string
}

const props = defineProps<Props>()

// Vérifications individuelles
const hasMinLength = computed(() => props.password.length >= 8)
const hasLowercase = computed(() => /[a-z]/.test(props.password))
const hasUppercase = computed(() => /[A-Z]/.test(props.password))
const hasNumber = computed(() => /\d/.test(props.password))
const hasSpecialChar = computed(() => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(props.password))

// Calcul de la force
const strengthScore = computed(() => {
  let score = 0
  
  if (hasMinLength.value) score += 20
  if (hasLowercase.value) score += 20
  if (hasUppercase.value) score += 20
  if (hasNumber.value) score += 20
  if (hasSpecialChar.value) score += 20
  
  // Bonus pour la longueur
  if (props.password.length >= 12) score += 10
  if (props.password.length >= 16) score += 10
  
  return Math.min(score, 100)
})

const strengthPercentage = computed(() => strengthScore.value)

const strengthLevel = computed(() => {
  if (strengthScore.value < 20) return 'very-weak'
  if (strengthScore.value < 40) return 'weak'
  if (strengthScore.value < 70) return 'medium'
  if (strengthScore.value < 90) return 'strong'
  return 'very-strong'
})

const strengthText = computed(() => {
  switch (strengthLevel.value) {
    case 'very-weak': return 'Très faible'
    case 'weak': return 'Faible'
    case 'medium': return 'Moyen'
    case 'strong': return 'Fort'
    case 'very-strong': return 'Très fort'
    default: return ''
  }
})

const strengthColor = computed(() => {
  switch (strengthLevel.value) {
    case 'very-weak': return 'bg-red-500'
    case 'weak': return 'bg-orange-500'
    case 'medium': return 'bg-yellow-500'
    case 'strong': return 'bg-blue-500'
    case 'very-strong': return 'bg-green-500'
    default: return 'bg-gray-300'
  }
})

const strengthTextColor = computed(() => {
  switch (strengthLevel.value) {
    case 'very-weak': return 'text-red-600 dark:text-red-400'
    case 'weak': return 'text-orange-600 dark:text-orange-400'
    case 'medium': return 'text-yellow-600 dark:text-yellow-400'
    case 'strong': return 'text-blue-600 dark:text-blue-400'
    case 'very-strong': return 'text-green-600 dark:text-green-400'
    default: return 'text-gray-500 dark:text-gray-400'
  }
})
</script>

<script lang="ts">
/**
 * Composant pour afficher une icône de validation
 */
const CheckIcon = {
  props: {
    valid: {
      type: Boolean,
      required: true
    }
  },
  template: `
    <svg 
      class="h-4 w-4 mr-2 flex-shrink-0"
      :class="valid ? 'text-green-500' : 'text-gray-300 dark:text-gray-600'"
      fill="none" 
      stroke="currentColor" 
      viewBox="0 0 24 24"
    >
      <path 
        v-if="valid"
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
      />
      <circle 
        v-else
        cx="12" 
        cy="12" 
        r="10"
        stroke-width="2"
      />
    </svg>
  `
}

export default {
  name: 'PasswordStrengthIndicator',
  components: {
    CheckIcon
  }
}
</script>