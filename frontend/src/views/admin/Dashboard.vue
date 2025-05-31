<template>
  <div class="container-app section-padding max-w-7xl">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Tableau de bord administrateur
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          Vue d'ensemble et statistiques de la plateforme
        </p>
      </div>
      
      <!-- Actions rapides -->
      <div class="flex items-center gap-3">
        <select
          v-model="selectedPeriod"
          @change="loadDashboardData"
          class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
        >
          <option value="24h">Dernières 24h</option>
          <option value="7d">7 derniers jours</option>
          <option value="30d">30 derniers jours</option>
          <option value="90d">3 derniers mois</option>
        </select>
        
        <Button
          variant="secondary"
          @click="refreshData"
          :loading="refreshing"
          size="sm"
        >
          <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Actualiser
        </Button>
      </div>
    </div>

    <!-- Cartes de statistiques principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <AdminStatsCard
        title="Utilisateurs actifs"
        :value="stats.activeUsers"
        :change="stats.activeUsersChange"
        icon="UsersIcon"
        color="blue"
        :loading="loading.stats"
      />
      <AdminStatsCard
        title="Transcriptions aujourd'hui"
        :value="stats.transcriptionsToday"
        :change="stats.transcriptionsTodayChange"
        icon="DocumentTextIcon"
        color="green"
        :loading="loading.stats"
      />
      <AdminStatsCard
        title="Revenus du mois"
        :value="formatCurrency(stats.monthlyRevenue)"
        :change="stats.monthlyRevenueChange"
        icon="CurrencyEuroIcon"
        color="yellow"
        :loading="loading.stats"
      />
      <AdminStatsCard
        title="Stockage utilisé"
        :value="formatStorage(stats.storageUsed)"
        :change="stats.storageUsedChange"
        icon="DatabaseIcon"
        color="purple"
        :loading="loading.stats"
      />
    </div>

    <!-- Section principales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Graphique d'activité -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Activité de la plateforme
            </h3>
            <div class="flex gap-2">
              <button
                v-for="metric in chartMetrics"
                :key="metric.key"
                @click="selectedMetric = metric.key"
                :class="[
                  'px-3 py-1 text-sm rounded-full transition-colors',
                  selectedMetric === metric.key
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                {{ metric.label }}
              </button>
            </div>
          </div>
          <ActivityChart
            :data="activityData"
            :metric="selectedMetric"
            :loading="loading.activity"
          />
        </div>
      </div>

      <!-- Utilisateurs récents -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Nouveaux utilisateurs
          </h3>
          <router-link
            to="/admin/users"
            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
          >
            Voir tous →
          </router-link>
        </div>
        <RecentUsers
          :users="recentUsers"
          :loading="loading.users"
        />
      </div>
    </div>

    <!-- Section système -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- État du système -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
          État du système
        </h3>
        <SystemStatus
          :status="systemStatus"
          :loading="loading.system"
          @refresh="loadSystemStatus"
        />
      </div>

      <!-- Activité récente -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Activité récente
          </h3>
          <router-link
            to="/admin/logs"
            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
          >
            Voir logs →
          </router-link>
        </div>
        <RecentActivity
          :activities="recentActivities"
          :loading="loading.activities"
        />
      </div>
    </div>

    <!-- Section alertes et rapports -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Alertes système -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Alertes système
          </h3>
          <span
            v-if="alerts.length > 0"
            class="bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 text-xs px-2 py-1 rounded-full"
          >
            {{ alerts.length }}
          </span>
        </div>
        <SystemAlerts
          :alerts="alerts"
          :loading="loading.alerts"
          @dismiss="dismissAlert"
          @resolve="resolveAlert"
        />
      </div>

      <!-- Métriques de performance -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
          Performance
        </h3>
        <PerformanceMetrics
          :metrics="performanceMetrics"
          :loading="loading.performance"
        />
      </div>

      <!-- Actions rapides -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
          Actions rapides
        </h3>
        <QuickActions
          @broadcast="showBroadcastModal = true"
          @maintenance="toggleMaintenanceMode"
          @backup="startBackup"
          @clear-cache="clearSystemCache"
          :loading="actionLoading"
        />
      </div>
    </div>

    <!-- Modal de diffusion -->
    <BroadcastModal
      v-if="showBroadcastModal"
      @close="showBroadcastModal = false"
      @send="sendBroadcast"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import Button from '@/components/ui/Button.vue'
import AdminStatsCard from '@/components/admin/AdminStatsCard.vue'
import ActivityChart from '@/components/admin/ActivityChart.vue'
import RecentUsers from '@/components/admin/RecentUsers.vue'
import SystemStatus from '@/components/admin/SystemStatus.vue'
import RecentActivity from '@/components/admin/RecentActivity.vue'
import SystemAlerts from '@/components/admin/SystemAlerts.vue'
import PerformanceMetrics from '@/components/admin/PerformanceMetrics.vue'
import QuickActions from '@/components/admin/QuickActions.vue'
import BroadcastModal from '@/components/admin/BroadcastModal.vue'
import { useUIStore } from '@/stores/ui'

interface AdminStats {
  activeUsers: number
  activeUsersChange: number
  transcriptionsToday: number
  transcriptionsTodayChange: number
  monthlyRevenue: number
  monthlyRevenueChange: number
  storageUsed: number
  storageUsedChange: number
}

interface ActivityDataPoint {
  date: string
  users: number
  transcriptions: number
  revenue: number
  storage: number
}

interface RecentUser {
  id: string
  name: string
  email: string
  avatar?: string
  registeredAt: string
  status: 'active' | 'pending' | 'suspended'
}

interface SystemStatusData {
  api: 'operational' | 'degraded' | 'outage'
  database: 'operational' | 'degraded' | 'outage'
  storage: 'operational' | 'degraded' | 'outage'
  transcription: 'operational' | 'degraded' | 'outage'
  uptime: number
  lastCheck: string
}

interface Alert {
  id: string
  type: 'error' | 'warning' | 'info'
  title: string
  message: string
  timestamp: string
  resolved?: boolean
}

const uiStore = useUIStore()

// État réactif
const selectedPeriod = ref('7d')
const selectedMetric = ref('users')
const refreshing = ref(false)
const showBroadcastModal = ref(false)
const actionLoading = ref<string | null>(null)

// Données
const stats = reactive<AdminStats>({
  activeUsers: 0,
  activeUsersChange: 0,
  transcriptionsToday: 0,
  transcriptionsTodayChange: 0,
  monthlyRevenue: 0,
  monthlyRevenueChange: 0,
  storageUsed: 0,
  storageUsedChange: 0
})

const activityData = ref<ActivityDataPoint[]>([])
const recentUsers = ref<RecentUser[]>([])
const systemStatus = ref<SystemStatusData>({
  api: 'operational',
  database: 'operational',
  storage: 'operational',
  transcription: 'operational',
  uptime: 99.9,
  lastCheck: new Date().toISOString()
})
const recentActivities = ref<any[]>([])
const alerts = ref<Alert[]>([])
const performanceMetrics = ref<any>({})

// États de chargement
const loading = reactive({
  stats: false,
  activity: false,
  users: false,
  system: false,
  activities: false,
  alerts: false,
  performance: false
})

// Configuration des métriques de graphique
const chartMetrics = [
  { key: 'users', label: 'Utilisateurs' },
  { key: 'transcriptions', label: 'Transcriptions' },
  { key: 'revenue', label: 'Revenus' },
  { key: 'storage', label: 'Stockage' }
]

/**
 * Charger toutes les données du dashboard
 */
async function loadDashboardData() {
  await Promise.all([
    loadStats(),
    loadActivityData(),
    loadRecentUsers(),
    loadSystemStatus(),
    loadRecentActivities(),
    loadAlerts(),
    loadPerformanceMetrics()
  ])
}

/**
 * Charger les statistiques principales
 */
async function loadStats() {
  try {
    loading.stats = true
    
    // TODO: Remplacer par un vrai appel API
    // Données simulées
    await new Promise(resolve => setTimeout(resolve, 800))
    
    Object.assign(stats, {
      activeUsers: 1247,
      activeUsersChange: 12.5,
      transcriptionsToday: 89,
      transcriptionsTodayChange: 8.3,
      monthlyRevenue: 15642.50,
      monthlyRevenueChange: -2.1,
      storageUsed: 2048576, // en bytes
      storageUsedChange: 15.2
    })
  } catch (error) {
    console.error('Erreur lors du chargement des statistiques:', error)
  } finally {
    loading.stats = false
  }
}

/**
 * Charger les données d'activité
 */
async function loadActivityData() {
  try {
    loading.activity = true
    
    // TODO: Remplacer par un vrai appel API
    const mockData: ActivityDataPoint[] = []
    const now = new Date()
    
    for (let i = 29; i >= 0; i--) {
      const date = new Date(now)
      date.setDate(date.getDate() - i)
      
      mockData.push({
        date: date.toISOString().split('T')[0],
        users: Math.floor(Math.random() * 100) + 50,
        transcriptions: Math.floor(Math.random() * 200) + 100,
        revenue: Math.random() * 1000 + 500,
        storage: Math.random() * 50 + 20
      })
    }
    
    activityData.value = mockData
  } catch (error) {
    console.error('Erreur lors du chargement des données d\'activité:', error)
  } finally {
    loading.activity = false
  }
}

/**
 * Charger les utilisateurs récents
 */
async function loadRecentUsers() {
  try {
    loading.users = true
    
    // TODO: Remplacer par un vrai appel API
    recentUsers.value = [
      {
        id: '1',
        name: 'Marie Dubois',
        email: 'marie.dubois@example.com',
        registeredAt: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
        status: 'active'
      },
      {
        id: '2',
        name: 'Pierre Martin',
        email: 'pierre.martin@example.com',
        registeredAt: new Date(Date.now() - 4 * 60 * 60 * 1000).toISOString(),
        status: 'pending'
      },
      {
        id: '3',
        name: 'Sophie Bernard',
        email: 'sophie.bernard@example.com',
        registeredAt: new Date(Date.now() - 6 * 60 * 60 * 1000).toISOString(),
        status: 'active'
      }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs récents:', error)
  } finally {
    loading.users = false
  }
}

/**
 * Charger l'état du système
 */
async function loadSystemStatus() {
  try {
    loading.system = true
    
    // TODO: Remplacer par un vrai appel API
    await new Promise(resolve => setTimeout(resolve, 500))
    
    systemStatus.value = {
      api: 'operational',
      database: 'operational',
      storage: 'degraded',
      transcription: 'operational',
      uptime: 99.7,
      lastCheck: new Date().toISOString()
    }
  } catch (error) {
    console.error('Erreur lors du chargement de l\'état du système:', error)
  } finally {
    loading.system = false
  }
}

/**
 * Charger l'activité récente
 */
async function loadRecentActivities() {
  try {
    loading.activities = true
    
    // TODO: Remplacer par un vrai appel API
    recentActivities.value = [
      {
        id: '1',
        type: 'user_registration',
        message: 'Nouvel utilisateur inscrit: marie.dubois@example.com',
        timestamp: new Date(Date.now() - 30 * 60 * 1000).toISOString(),
        severity: 'info'
      },
      {
        id: '2',
        type: 'system_alert',
        message: 'Utilisation du stockage à 85%',
        timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
        severity: 'warning'
      },
      {
        id: '3',
        type: 'transcription_completed',
        message: '245 transcriptions traitées avec succès',
        timestamp: new Date(Date.now() - 4 * 60 * 60 * 1000).toISOString(),
        severity: 'success'
      }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement de l\'activité récente:', error)
  } finally {
    loading.activities = false
  }
}

/**
 * Charger les alertes
 */
async function loadAlerts() {
  try {
    loading.alerts = true
    
    // TODO: Remplacer par un vrai appel API
    alerts.value = [
      {
        id: '1',
        type: 'warning',
        title: 'Stockage bientôt plein',
        message: 'L\'espace de stockage atteindra 90% dans les prochaines 24h',
        timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString()
      },
      {
        id: '2',
        type: 'info',
        title: 'Maintenance programmée',
        message: 'Maintenance du serveur prévue demain de 2h à 4h',
        timestamp: new Date(Date.now() - 6 * 60 * 60 * 1000).toISOString()
      }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement des alertes:', error)
  } finally {
    loading.alerts = false
  }
}

/**
 * Charger les métriques de performance
 */
async function loadPerformanceMetrics() {
  try {
    loading.performance = true
    
    // TODO: Remplacer par un vrai appel API
    performanceMetrics.value = {
      avgResponseTime: 245,
      requestsPerSecond: 127,
      errorRate: 0.8,
      cpuUsage: 45,
      memoryUsage: 67,
      diskUsage: 82
    }
  } catch (error) {
    console.error('Erreur lors du chargement des métriques de performance:', error)
  } finally {
    loading.performance = false
  }
}

/**
 * Actualiser toutes les données
 */
async function refreshData() {
  refreshing.value = true
  try {
    await loadDashboardData()
    uiStore.showNotification({
      type: 'success',
      title: 'Données actualisées',
      message: 'Les données du dashboard ont été mises à jour'
    })
  } catch (error) {
    console.error('Erreur lors de l\'actualisation:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur',
      message: 'Impossible d\'actualiser les données'
    })
  } finally {
    refreshing.value = false
  }
}

/**
 * Rejeter une alerte
 */
async function dismissAlert(alertId: string) {
  try {
    // TODO: Appel API pour rejeter l'alerte
    alerts.value = alerts.value.filter(alert => alert.id !== alertId)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Alerte rejetée',
      message: 'L\'alerte a été supprimée'
    })
  } catch (error) {
    console.error('Erreur lors du rejet de l\'alerte:', error)
  }
}

/**
 * Résoudre une alerte
 */
async function resolveAlert(alertId: string) {
  try {
    // TODO: Appel API pour résoudre l'alerte
    const alert = alerts.value.find(a => a.id === alertId)
    if (alert) {
      alert.resolved = true
    }
    
    uiStore.showNotification({
      type: 'success',
      title: 'Alerte résolue',
      message: 'L\'alerte a été marquée comme résolue'
    })
  } catch (error) {
    console.error('Erreur lors de la résolution de l\'alerte:', error)
  }
}

/**
 * Basculer le mode maintenance
 */
async function toggleMaintenanceMode() {
  actionLoading.value = 'maintenance'
  try {
    // TODO: Appel API pour basculer le mode maintenance
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    uiStore.showNotification({
      type: 'info',
      title: 'Mode maintenance',
      message: 'Le mode maintenance a été activé'
    })
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    actionLoading.value = null
  }
}

/**
 * Démarrer une sauvegarde
 */
async function startBackup() {
  actionLoading.value = 'backup'
  try {
    // TODO: Appel API pour démarrer une sauvegarde
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    uiStore.showNotification({
      type: 'success',
      title: 'Sauvegarde démarrée',
      message: 'La sauvegarde du système a été lancée'
    })
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    actionLoading.value = null
  }
}

/**
 * Vider le cache système
 */
async function clearSystemCache() {
  actionLoading.value = 'cache'
  try {
    // TODO: Appel API pour vider le cache
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    uiStore.showNotification({
      type: 'success',
      title: 'Cache vidé',
      message: 'Le cache système a été vidé avec succès'
    })
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    actionLoading.value = null
  }
}

/**
 * Envoyer une diffusion
 */
async function sendBroadcast(message: { title: string; content: string; type: string }) {
  try {
    // TODO: Appel API pour envoyer la diffusion
    console.log('Envoi de diffusion:', message)
    
    uiStore.showNotification({
      type: 'success',
      title: 'Message envoyé',
      message: 'La diffusion a été envoyée à tous les utilisateurs'
    })
    
    showBroadcastModal.value = false
  } catch (error) {
    console.error('Erreur lors de l\'envoi de la diffusion:', error)
    uiStore.showNotification({
      type: 'error',
      title: 'Erreur d\'envoi',
      message: 'Impossible d\'envoyer la diffusion'
    })
  }
}

/**
 * Formater la devise
 */
function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

/**
 * Formater le stockage
 */
function formatStorage(bytes: number): string {
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let size = bytes
  let unitIndex = 0
  
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex++
  }
  
  return `${size.toFixed(1)} ${units[unitIndex]}`
}

// Lifecycle
onMounted(() => {
  loadDashboardData()
})
</script>

<script lang="ts">
export default {
  name: 'AdminDashboard'
}
</script>