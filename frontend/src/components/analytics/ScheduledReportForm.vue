<template>
  <div class="space-y-6">
    <!-- Report basic info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Nom du rapport
        </label>
        <input
          v-model="form.name"
          type="text"
          placeholder="Ex: Rapport mensuel des coûts"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          required
        />
      </div>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Type de rapport
        </label>
        <select
          v-model="form.type"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          required
        >
          <option value="">Sélectionner un type</option>
          <option value="overview">Vue d'ensemble</option>
          <option value="performance">Performance</option>
          <option value="costs">Coûts</option>
          <option value="custom">Personnalisé</option>
        </select>
      </div>
    </div>
    
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Description (optionnel)
      </label>
      <textarea
        v-model="form.description"
        rows="3"
        placeholder="Description du rapport..."
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
      ></textarea>
    </div>
    
    <!-- Schedule configuration -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
        Programmation
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Fréquence
          </label>
          <select
            v-model="form.frequency"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            required
          >
            <option value="">Sélectionner une fréquence</option>
            <option value="daily">Quotidien</option>
            <option value="weekly">Hebdomadaire</option>
            <option value="monthly">Mensuel</option>
          </select>
        </div>
        
        <div v-if="form.frequency === 'weekly'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Jour de la semaine
          </label>
          <select
            v-model="form.dayOfWeek"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option value="0">Dimanche</option>
            <option value="1">Lundi</option>
            <option value="2">Mardi</option>
            <option value="3">Mercredi</option>
            <option value="4">Jeudi</option>
            <option value="5">Vendredi</option>
            <option value="6">Samedi</option>
          </select>
        </div>
        
        <div v-if="form.frequency === 'monthly'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Jour du mois
          </label>
          <select
            v-model="form.dayOfMonth"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option v-for="day in 31" :key="day" :value="day">{{ day }}</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Heure d'envoi
          </label>
          <input
            v-model="form.time"
            type="time"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            required
          />
        </div>
      </div>
    </div>
    
    <!-- Report configuration -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
        Configuration du rapport
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Période de données
          </label>
          <select
            v-model="form.period"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            required
          >
            <option value="7d">7 derniers jours</option>
            <option value="30d">30 derniers jours</option>
            <option value="90d">3 derniers mois</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Format de sortie
          </label>
          <select
            v-model="form.format"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            required
          >
            <option value="pdf">PDF</option>
            <option value="excel">Excel</option>
            <option value="csv">CSV</option>
          </select>
        </div>
      </div>
      
      <!-- Sections to include -->
      <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Sections à inclure
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <label
            v-for="section in availableSections"
            :key="section.id"
            class="flex items-center"
          >
            <input
              v-model="form.sections"
              :value="section.id"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
            />
            <div class="ml-3">
              <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ section.name }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ section.description }}
              </div>
            </div>
          </label>
        </div>
      </div>
    </div>
    
    <!-- Recipients -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
        Destinataires
      </h3>
      
      <div class="space-y-4">
        <!-- Add recipient -->
        <div class="flex space-x-2">
          <input
            v-model="newRecipient"
            type="email"
            placeholder="adresse@email.com"
            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            @keyup.enter="addRecipient"
          />
          <button
            type="button"
            @click="addRecipient"
            :disabled="!isValidEmail(newRecipient)"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Ajouter
          </button>
        </div>
        
        <!-- Recipients list -->
        <div v-if="form.recipients.length > 0" class="space-y-2">
          <div
            v-for="(email, index) in form.recipients"
            :key="index"
            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
          >
            <span class="text-sm text-gray-900 dark:text-white">{{ email }}</span>
            <button
              type="button"
              @click="removeRecipient(index)"
              class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
            >
              <XIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
        
        <p v-if="form.recipients.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
          Aucun destinataire configuré. Ajoutez au moins une adresse email.
        </p>
      </div>
    </div>
    
    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
      >
        Annuler
      </button>
      <button
        type="button"
        @click="submit"
        :disabled="!isFormValid"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ editMode ? 'Mettre à jour' : 'Créer' }} le rapport
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue'

interface ScheduledReportForm {
  name: string
  description: string
  type: string
  frequency: string
  dayOfWeek?: number
  dayOfMonth?: number
  time: string
  period: string
  format: string
  sections: string[]
  recipients: string[]
}

interface ScheduledReport {
  id: string
  name: string
  description: string
  type: string
  frequency: string
  dayOfWeek?: number
  dayOfMonth?: number
  time: string
  period: string
  format: string
  sections: string[]
  recipients: string[]
}

interface Props {
  report?: ScheduledReport | null
}

interface Emits {
  (e: 'submit', data: ScheduledReportForm): void
  (e: 'cancel'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// State
const newRecipient = ref('')

const form = reactive<ScheduledReportForm>({
  name: '',
  description: '',
  type: '',
  frequency: '',
  dayOfWeek: 1,
  dayOfMonth: 1,
  time: '09:00',
  period: '30d',
  format: 'pdf',
  sections: ['statistics', 'usage'],
  recipients: []
})

const availableSections = [
  { id: 'statistics', name: 'Statistiques générales', description: 'Métriques clés et tendances' },
  { id: 'usage', name: 'Utilisation', description: 'Données d\'utilisation détaillées' },
  { id: 'costs', name: 'Coûts', description: 'Analyse des coûts et projections' },
  { id: 'performance', name: 'Performance', description: 'Temps de traitement et qualité' },
  { id: 'errors', name: 'Erreurs', description: 'Journal des erreurs et incidents' },
  { id: 'users', name: 'Utilisateurs', description: 'Activité par utilisateur' },
  { id: 'languages', name: 'Langues', description: 'Répartition par langue' },
  { id: 'optimization', name: 'Optimisations', description: 'Recommandations d\'amélioration' }
]

// Computed
const editMode = computed(() => !!props.report)

const isFormValid = computed(() => {
  return form.name &&
         form.type &&
         form.frequency &&
         form.time &&
         form.period &&
         form.format &&
         form.sections.length > 0 &&
         form.recipients.length > 0
})

// Watch for prop changes
watch(
  () => props.report,
  (report) => {
    if (report) {
      Object.assign(form, {
        name: report.name,
        description: report.description,
        type: report.type,
        frequency: report.frequency,
        dayOfWeek: report.dayOfWeek || 1,
        dayOfMonth: report.dayOfMonth || 1,
        time: report.time,
        period: report.period,
        format: report.format,
        sections: [...report.sections],
        recipients: [...report.recipients]
      })
    } else {
      // Reset form for new report
      Object.assign(form, {
        name: '',
        description: '',
        type: '',
        frequency: '',
        dayOfWeek: 1,
        dayOfMonth: 1,
        time: '09:00',
        period: '30d',
        format: 'pdf',
        sections: ['statistics', 'usage'],
        recipients: []
      })
    }
  },
  { immediate: true }
)

// Methods
function isValidEmail(email: string): boolean {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

function addRecipient() {
  if (newRecipient.value && isValidEmail(newRecipient.value)) {
    if (!form.recipients.includes(newRecipient.value)) {
      form.recipients.push(newRecipient.value)
      newRecipient.value = ''
    }
  }
}

function removeRecipient(index: number) {
  form.recipients.splice(index, 1)
}

function submit() {
  if (isFormValid.value) {
    emit('submit', { ...form })
  }
}

// Icons
const XIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
  `
}
</script>