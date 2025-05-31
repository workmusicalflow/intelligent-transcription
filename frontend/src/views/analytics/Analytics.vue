<template>
  <div class="container-app section-padding max-w-7xl">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Analytiques
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          Tableaux de bord et statistiques d'utilisation
        </p>
      </div>
      
      <!-- Sélecteur de période -->
      <div class="flex items-center gap-4">
        <select
          v-model="selectedPeriod"
          @change="loadAnalytics"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
        >
          <option value="7d">7 derniers jours</option>
          <option value="30d">30 derniers jours</option>
          <option value="90d">3 derniers mois</option>
          <option value="1y">12 derniers mois</option>
        </select>
        
        <Button
          variant="secondary"
          @click="exportReport"
          :loading="exporting"
        >
          <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
          </svg>
          Exporter
        </Button>
      </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatsCard
        title="Transcriptions"
        :value="stats.totalTranscriptions"
        :change="stats.transcriptionsChange"
        icon="DocumentTextIcon"
        color="blue"
      />
      <StatsCard
        title="Heures audio"
        :value="formatHours(stats.totalAudioHours)"
        :change="stats.audioHoursChange"
        icon="VolumeUpIcon"
        color="green"
      />
      <StatsCard
        title="Coût total"
        :value="formatCurrency(stats.totalCost)"
        :change="stats.costChange"
        icon="CurrencyEuroIcon"
        color="yellow"
      />
      <StatsCard
        title="Temps de traitement"
        :value="formatDuration(stats.avgProcessingTime)"
        :change="stats.processingTimeChange"
        icon="ClockIcon"
        color="purple"
      />
    </div>

    <!-- Navigation des onglets -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-8">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
          ]"
        >
          <div class="flex items-center">
            <component :is="tab.icon" class="h-5 w-5 mr-2" />
            {{ tab.name }}
          </div>
        </button>
      </nav>
    </div>

    <!-- Contenu des onglets -->
    <div class="space-y-8">
      <!-- Onglet Vue d'ensemble -->
      <div v-show="activeTab === 'overview'">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Graphique d'utilisation -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Évolution des transcriptions
            </h3>
            <UsageChart
              :data="usageData"
              :loading="loading.usage"
              type="transcriptions"
            />
          </div>
          
          <!-- Répartition par langue -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Répartition par langue
            </h3>
            <LanguageChart
              :data="languageData"
              :loading="loading.languages"
            />
          </div>
        </div>
        
        <!-- Activité récente -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-8">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Activité récente
          </h3>
          <RecentActivity
            :activities="recentActivities"
            :loading="loading.activities"
          />
        </div>
      </div>

      <!-- Onglet Performance -->
      <div v-show="activeTab === 'performance'">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Temps de traitement -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Temps de traitement
            </h3>
            <PerformanceChart
              :data="performanceData"
              :loading="loading.performance"
              type="processing-time"
            />
          </div>
          
          <!-- Taux de succès -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Taux de succès
            </h3>
            <SuccessRateChart
              :data="successRateData"
              :loading="loading.successRate"
            />
          </div>
        </div>
        
        <!-- Métriques de performance -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-8">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Métriques détaillées
          </h3>
          <PerformanceMetrics
            :metrics="performanceMetrics"
            :loading="loading.metrics"
          />
        </div>
      </div>

      <!-- Onglet Coûts -->
      <div v-show="activeTab === 'costs'">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Évolution des coûts -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Évolution des coûts
            </h3>
            <CostChart
              :data="costData"
              :loading="loading.costs"
            />
          </div>
          
          <!-- Répartition des coûts -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Répartition par service
            </h3>
            <CostBreakdownChart
              :data="costBreakdownData"
              :loading="loading.costBreakdown"
            />
          </div>
        </div>
        
        <!-- Prévisions de coûts -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-8">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Prévisions et optimisation
          </h3>
          <CostOptimization
            :data="costOptimizationData"
            :loading="loading.costOptimization"
          />
        </div>
      </div>

      <!-- Onglet Rapports -->
      <div v-show="activeTab === 'reports'">
        <div class="space-y-8">
          <!-- Générateur de rapports -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Générateur de rapports
            </h3>
            <ReportGenerator
              @generate="generateReport"
              :loading="loading.reportGeneration"
            />
          </div>
          
          <!-- Rapports programmés -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Rapports programmés
            </h3>
            <ScheduledReports
              :reports="scheduledReports"
              :loading="loading.scheduledReports"
              @create="createScheduledReport"
              @edit="editScheduledReport"
              @delete="deleteScheduledReport"
            />
          </div>
          
          <!-- Historique des rapports -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Historique des rapports
            </h3>
            <ReportHistory
              :reports="reportHistory"
              :loading="loading.reportHistory"
              @download="downloadReport"
              @delete="deleteReport"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import Button from '@/components/ui/Button.vue'
import StatsCard from '@/components/analytics/StatsCard.vue'
import UsageChart from '@/components/analytics/UsageChart.vue'
import LanguageChart from '@/components/analytics/LanguageChart.vue'
import RecentActivity from '@/components/analytics/RecentActivity.vue'
import PerformanceChart from '@/components/analytics/PerformanceChart.vue'
import SuccessRateChart from '@/components/analytics/SuccessRateChart.vue'
import PerformanceMetrics from '@/components/analytics/PerformanceMetrics.vue'
import CostChart from '@/components/analytics/CostChart.vue'
import CostBreakdownChart from '@/components/analytics/CostBreakdownChart.vue'
import CostOptimization from '@/components/analytics/CostOptimization.vue'
import ReportGenerator from '@/components/analytics/ReportGenerator.vue'
import ScheduledReports from '@/components/analytics/ScheduledReports.vue'
import ReportHistory from '@/components/analytics/ReportHistory.vue'
import { useUIStore } from '@/stores/ui'

interface AnalyticsStats {
  totalTranscriptions: number
  transcriptionsChange: number
  totalAudioHours: number
  audioHoursChange: number
  totalCost: number
  costChange: number
  avgProcessingTime: number
  processingTimeChange: number
}

interface UsageDataPoint {
  date: string
  transcriptions: number
  audioHours: number
  cost: number
}

interface LanguageDataPoint {
  language: string
  count: number
  percentage: number
  color: string
}

interface Activity {
  id: string
  type: 'transcription' | 'error' | 'optimization' | 'report'
  message: string
  timestamp: string
  metadata?: Record<string, any>
}

const uiStore = useUIStore()

// État réactif
const activeTab = ref('overview')
const selectedPeriod = ref('30d')
const exporting = ref(false)

// Statistiques principales
const stats = reactive<AnalyticsStats>({
  totalTranscriptions: 0,
  transcriptionsChange: 0,
  totalAudioHours: 0,
  audioHoursChange: 0,
  totalCost: 0,
  costChange: 0,
  avgProcessingTime: 0,
  processingTimeChange: 0
})

// Données pour les graphiques
const usageData = ref<UsageDataPoint[]>([])
const languageData = ref<LanguageDataPoint[]>([])
const performanceData = ref<any[]>([])
const successRateData = ref<any[]>([])
const costData = ref<any[]>([])
const costBreakdownData = ref<any[]>([])
const performanceMetrics = ref<any>({})
const costOptimizationData = ref<any>({})
const recentActivities = ref<Activity[]>([])
const scheduledReports = ref<any[]>([])
const reportHistory = ref<any[]>([])

// États de chargement
const loading = reactive({
  usage: false,
  languages: false,
  activities: false,
  performance: false,
  successRate: false,
  metrics: false,
  costs: false,
  costBreakdown: false,
  costOptimization: false,
  reportGeneration: false,
  scheduledReports: false,
  reportHistory: false
})

// Configuration des onglets
const tabs = computed(() => [
  {
    id: 'overview',
    name: 'Vue d\'ensemble',
    icon: 'ChartBarIcon'
  },
  {
    id: 'performance',
    name: 'Performance',
    icon: 'LightningBoltIcon'
  },
  {
    id: 'costs',
    name: 'Coûts',
    icon: 'CurrencyEuroIcon'
  },
  {
    id: 'reports',
    name: 'Rapports',
    icon: 'DocumentReportIcon'
  }
])

/**
 * Charger les analytics
 */
async function loadAnalytics() {
  try {
    await Promise.all([
      loadStats(),
      loadUsageData(),
      loadLanguageData(),
      loadRecentActivities()
    ])
  } catch (error) {
    console.error('Erreur lors du chargement des analytics:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de charger les données analytiques'
    })
  }
}

/**
 * Charger les statistiques principales
 */
async function loadStats() {
  try {
    // TODO: Implémenter l'appel API
    // Données simulées
    Object.assign(stats, {
      totalTranscriptions: 1247,
      transcriptionsChange: 12.5,
      totalAudioHours: 342.8,
      audioHoursChange: 8.3,
      totalCost: 156.42,
      costChange: -2.1,
      avgProcessingTime: 45,
      processingTimeChange: -15.2
    })
  } catch (error) {
    console.error('Erreur lors du chargement des statistiques:', error)
  }
}

/**
 * Charger les données d'utilisation
 */
async function loadUsageData() {
  try {
    loading.usage = true
    
    // TODO: Implémenter l'appel API
    // Données simulées
    const mockData: UsageDataPoint[] = []
    const now = new Date()
    
    for (let i = 29; i >= 0; i--) {
      const date = new Date(now)
      date.setDate(date.getDate() - i)
      
      mockData.push({
        date: date.toISOString().split('T')[0],
        transcriptions: Math.floor(Math.random() * 50) + 10,
        audioHours: Math.random() * 20 + 5,
        cost: Math.random() * 15 + 2
      })
    }
    
    usageData.value = mockData
  } catch (error) {
    console.error('Erreur lors du chargement des données d\'utilisation:', error)
  } finally {
    loading.usage = false
  }
}

/**
 * Charger les données par langue
 */
async function loadLanguageData() {
  try {
    loading.languages = true
    
    // TODO: Implémenter l'appel API
    // Données simulées
    languageData.value = [
      { language: 'Français', count: 587, percentage: 47.1, color: '#3B82F6' },
      { language: 'Anglais', count: 342, percentage: 27.4, color: '#10B981' },
      { language: 'Espagnol', count: 186, percentage: 14.9, color: '#F59E0B' },
      { language: 'Allemand', count: 89, percentage: 7.1, color: '#EF4444' },
      { language: 'Italien', count: 43, percentage: 3.5, color: '#8B5CF6' }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement des données par langue:', error)
  } finally {
    loading.languages = false
  }
}

/**
 * Charger l'activité récente
 */
async function loadRecentActivities() {
  try {
    loading.activities = true
    
    // TODO: Implémenter l'appel API
    // Données simulées
    recentActivities.value = [
      {
        id: '1',
        type: 'transcription',
        message: 'Transcription de "Meeting_2024.mp3" terminée',
        timestamp: new Date(Date.now() - 5 * 60 * 1000).toISOString()
      },
      {
        id: '2',
        type: 'optimization',
        message: 'Cache vidé automatiquement (128 MB libérés)',
        timestamp: new Date(Date.now() - 25 * 60 * 1000).toISOString()
      },
      {
        id: '3',
        type: 'transcription',
        message: 'Transcription de "Interview_Client.wav" démarrée',
        timestamp: new Date(Date.now() - 45 * 60 * 1000).toISOString()
      },
      {
        id: '4',
        type: 'error',
        message: 'Échec de transcription : fichier audio corrompu',
        timestamp: new Date(Date.now() - 120 * 60 * 1000).toISOString()
      },
      {
        id: '5',
        type: 'report',
        message: 'Rapport mensuel généré et envoyé par email',
        timestamp: new Date(Date.now() - 180 * 60 * 1000).toISOString()
      }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement de l\'activité récente:', error)
  } finally {
    loading.activities = false
  }
}

/**
 * Exporter un rapport
 */
async function exportReport() {
  try {
    exporting.value = true
    
    // TODO: Implémenter l'export de rapport
    console.log('Export du rapport pour la période:', selectedPeriod.value)
    
    // Simuler un délai
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    uiStore.showNotification({
      type: 'success',
      title: 'Export réussi',
      message: 'Le rapport a été téléchargé avec succès'
    })
  } catch (error) {
    console.error('Erreur lors de l\'export:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur d\'export',
      message: 'Impossible d\'exporter le rapport'
    })
  } finally {
    exporting.value = false
  }
}

/**
 * Générer un rapport personnalisé
 */
async function generateReport(config: any) {
  try {
    loading.reportGeneration = true
    
    // TODO: Implémenter la génération de rapport
    console.log('Génération de rapport:', config)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Rapport généré',
      message: 'Le rapport personnalisé a été créé avec succès'
    })
  } catch (error) {
    console.error('Erreur lors de la génération:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible de générer le rapport'
    })
  } finally {
    loading.reportGeneration = false
  }
}

/**
 * Créer un rapport programmé
 */
async function createScheduledReport(config: any) {
  try {
    // TODO: Implémenter la création de rapport programmé
    console.log('Création rapport programmé:', config)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Rapport programmé',
      message: 'Le rapport a été programmé avec succès'
    })
  } catch (error) {
    console.error('Erreur:', error)
  }
}

/**
 * Modifier un rapport programmé
 */
async function editScheduledReport(id: string, config: any) {
  try {
    // TODO: Implémenter la modification
    console.log('Modification rapport programmé:', id, config)
  } catch (error) {
    console.error('Erreur:', error)
  }
}

/**
 * Supprimer un rapport programmé
 */
async function deleteScheduledReport(id: string) {
  try {
    // TODO: Implémenter la suppression
    console.log('Suppression rapport programmé:', id)
  } catch (error) {
    console.error('Erreur:', error)
  }
}

/**
 * Télécharger un rapport
 */
async function downloadReport(id: string) {
  try {
    // TODO: Implémenter le téléchargement
    console.log('Téléchargement rapport:', id)
  } catch (error) {
    console.error('Erreur:', error)
  }
}

/**
 * Supprimer un rapport
 */
async function deleteReport(id: string) {
  try {
    // TODO: Implémenter la suppression
    console.log('Suppression rapport:', id)
  } catch (error) {
    console.error('Erreur:', error)
  }
}

/**
 * Formater les heures
 */
function formatHours(hours: number): string {
  return `${hours.toFixed(1)}h`
}

/**
 * Formater la devise
 */
function formatCurrency(amount: number): string {
  return `${amount.toFixed(2)}€`
}

/**
 * Formater la durée
 */
function formatDuration(seconds: number): string {
  if (seconds < 60) {
    return `${seconds}s`
  } else if (seconds < 3600) {
    return `${Math.floor(seconds / 60)}min`
  } else {
    return `${Math.floor(seconds / 3600)}h ${Math.floor((seconds % 3600) / 60)}min`
  }
}

// Lifecycle
onMounted(() => {
  loadAnalytics()
})
</script>

<script lang="ts">
// Icônes pour les onglets
const ChartBarIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
    </svg>
  `
}

const LightningBoltIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
    </svg>
  `
}

const CurrencyEuroIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m3-9v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h8a2 2 0 012 2z"></path>
    </svg>
  `
}

const DocumentReportIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
  `
}

export default {
  name: 'Analytics',
  components: {
    ChartBarIcon,
    LightningBoltIcon,
    CurrencyEuroIcon,
    DocumentReportIcon
  }
}
</script>