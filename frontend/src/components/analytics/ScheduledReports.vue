<template>
  <div class="space-y-6">
    <!-- Add new scheduled report button -->
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          Rapports programmés
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Configurez des rapports automatiques réguliers
        </p>
      </div>
      <Button @click="showCreateModal = true">
        <PlusIcon class="h-4 w-4 mr-2" />
        Nouveau rapport
      </Button>
    </div>
    
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <LoadingSpinner size="lg" />
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="flex items-center justify-center py-8 text-red-500 dark:text-red-400">
      <div class="text-center">
        <ExclamationIcon class="h-12 w-12 mx-auto mb-2" />
        <p>Erreur lors du chargement des rapports programmés</p>
      </div>
    </div>
    
    <!-- Reports list -->
    <div v-else-if="reports.length > 0" class="space-y-4">
      <div
        v-for="report in reports"
        :key="report.id"
        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ report.name }}
              </h4>
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                getStatusBadgeColor(report.status)
              ]">
                {{ getStatusLabel(report.status) }}
              </span>
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              {{ report.description }}
            </p>
            
            <!-- Report details -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
              <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Fréquence
                </div>
                <div class="text-sm text-gray-900 dark:text-white mt-1">
                  {{ getFrequencyLabel(report.frequency) }}
                </div>
              </div>
              <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Prochaine exécution
                </div>
                <div class="text-sm text-gray-900 dark:text-white mt-1">
                  {{ formatDate(report.nextRun) }}
                </div>
              </div>
              <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Dernière exécution
                </div>
                <div class="text-sm text-gray-900 dark:text-white mt-1">
                  {{ report.lastRun ? formatDate(report.lastRun) : 'Jamais' }}
                </div>
              </div>
            </div>
            
            <!-- Recipients -->
            <div class="mt-4">
              <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                Destinataires
              </div>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="email in report.recipients"
                  :key="email"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                >
                  {{ email }}
                </span>
              </div>
            </div>
            
            <!-- Report configuration summary -->
            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
              <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                Configuration
              </div>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Type:</span>
                  <span class="text-gray-900 dark:text-white ml-1">{{ getReportTypeLabel(report.type) }}</span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Période:</span>
                  <span class="text-gray-900 dark:text-white ml-1">{{ getPeriodLabel(report.period) }}</span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Format:</span>
                  <span class="text-gray-900 dark:text-white ml-1">{{ report.format.toUpperCase() }}</span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Sections:</span>
                  <span class="text-gray-900 dark:text-white ml-1">{{ report.sections.length }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Actions -->
          <div class="flex items-center space-x-2 ml-4">
            <!-- Toggle active/inactive -->
            <button
              @click="toggleReport(report.id, !report.active)"
              :class="[
                'p-2 rounded-lg transition-colors',
                report.active 
                  ? 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' 
                  : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
              ]"
              :title="report.active ? 'Désactiver' : 'Activer'"
            >
              <component :is="report.active ? PlayIcon : PauseIcon" class="h-5 w-5" />
            </button>
            
            <!-- Run now -->
            <button
              @click="runReport(report.id)"
              class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
              title="Exécuter maintenant"
            >
              <PlayCircleIcon class="h-5 w-5" />
            </button>
            
            <!-- Edit -->
            <button
              @click="editReport(report)"
              class="p-2 text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
              title="Modifier"
            >
              <PencilIcon class="h-5 w-5" />
            </button>
            
            <!-- Delete -->
            <button
              @click="confirmDelete(report)"
              class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
              title="Supprimer"
            >
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <ClockIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        Aucun rapport programmé
      </h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        Créez votre premier rapport automatique pour recevoir des analyses régulières.
      </p>
      <Button @click="showCreateModal = true">
        <PlusIcon class="h-4 w-4 mr-2" />
        Créer un rapport programmé
      </Button>
    </div>
    
    <!-- Create/Edit Modal -->
    <ModalContainer
      v-if="showCreateModal"
      title="Nouveau rapport programmé"
      @close="closeModal"
    >
      <ScheduledReportForm
        :report="editingReport"
        @submit="handleSubmit"
        @cancel="closeModal"
      />
    </ModalContainer>
    
    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="reportToDelete"
      title="Supprimer le rapport programmé"
      :message="`Êtes-vous sûr de vouloir supprimer le rapport '${reportToDelete.name}' ? Cette action est irréversible.`"
      confirm-text="Supprimer"
      confirm-variant="danger"
      @confirm="deleteReport"
      @cancel="reportToDelete = null"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Button from '@/components/ui/Button.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import ModalContainer from '@/components/ui/ModalContainer.vue'
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue'
import ScheduledReportForm from './ScheduledReportForm.vue'

interface ScheduledReport {
  id: string
  name: string
  description: string
  type: string
  period: string
  frequency: 'daily' | 'weekly' | 'monthly'
  recipients: string[]
  format: string
  sections: string[]
  active: boolean
  status: 'active' | 'paused' | 'error'
  nextRun: string
  lastRun?: string
  createdAt: string
}

interface Props {
  reports: ScheduledReport[]
  loading?: boolean
  error?: boolean
}

interface Emits {
  (e: 'create', report: Partial<ScheduledReport>): void
  (e: 'edit', id: string, report: Partial<ScheduledReport>): void
  (e: 'delete', id: string): void
  (e: 'toggle', id: string, active: boolean): void
  (e: 'run', id: string): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: false
})

const emit = defineEmits<Emits>()

// State
const showCreateModal = ref(false)
const editingReport = ref<ScheduledReport | null>(null)
const reportToDelete = ref<ScheduledReport | null>(null)

// Methods
function getStatusBadgeColor(status: string): string {
  const colorMap = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    paused: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    error: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
  }
  return colorMap[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
}

function getStatusLabel(status: string): string {
  const labelMap = {
    active: 'Actif',
    paused: 'En pause',
    error: 'Erreur'
  }
  return labelMap[status] || 'Inconnu'
}

function getFrequencyLabel(frequency: string): string {
  const labelMap = {
    daily: 'Quotidien',
    weekly: 'Hebdomadaire',
    monthly: 'Mensuel'
  }
  return labelMap[frequency] || frequency
}

function getReportTypeLabel(type: string): string {
  const labelMap = {
    overview: 'Vue d\'ensemble',
    performance: 'Performance',
    costs: 'Coûts',
    custom: 'Personnalisé'
  }
  return labelMap[type] || type
}

function getPeriodLabel(period: string): string {
  const labelMap = {
    '7d': '7 jours',
    '30d': '30 jours',
    '90d': '3 mois',
    '1y': '1 an'
  }
  return labelMap[period] || period
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function editReport(report: ScheduledReport) {
  editingReport.value = report
  showCreateModal.value = true
}

function closeModal() {
  showCreateModal.value = false
  editingReport.value = null
}

function handleSubmit(reportData: Partial<ScheduledReport>) {
  if (editingReport.value) {
    emit('edit', editingReport.value.id, reportData)
  } else {
    emit('create', reportData)
  }
  closeModal()
}

function confirmDelete(report: ScheduledReport) {
  reportToDelete.value = report
}

function deleteReport() {
  if (reportToDelete.value) {
    emit('delete', reportToDelete.value.id)
    reportToDelete.value = null
  }
}

function toggleReport(id: string, active: boolean) {
  emit('toggle', id, active)
}

function runReport(id: string) {
  emit('run', id)
}

// Icons
const PlusIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
  `
}

const ExclamationIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
    </svg>
  `
}

const ClockIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

const PlayIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 24 24">
      <path d="M8 5v14l11-7z"/>
    </svg>
  `
}

const PauseIcon = {
  template: `
    <svg fill="currentColor" viewBox="0 0 24 24">
      <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
    </svg>
  `
}

const PlayCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m3-9v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h8a2 2 0 012 2z"></path>
    </svg>
  `
}

const PencilIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
    </svg>
  `
}

const TrashIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
    </svg>
  `
}
</script>