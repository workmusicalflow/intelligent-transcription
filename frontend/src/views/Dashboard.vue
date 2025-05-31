<template>
  <div class="container-app section-padding">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        Tableau de bord
      </h1>
      <p class="text-gray-600 dark:text-gray-400">
        Bienvenue {{ authStore.user?.name }}! Voici un aperçu de votre activité.
      </p>
    </div>

    <!-- Quick actions -->
    <div class="mb-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <QuickActionCard
          title="Nouvelle transcription"
          description="Créer une transcription audio"
          icon="DocumentPlusIcon"
          to="/transcriptions/create"
          color="primary"
        />
        <QuickActionCard
          title="Chat contextuel"
          description="Démarrer une conversation"
          icon="ChatBubbleLeftRightIcon"
          to="/chat"
          color="blue"
        />
        <QuickActionCard
          title="Mes transcriptions"
          description="Gérer mes fichiers"
          icon="DocumentTextIcon"
          to="/transcriptions"
          color="green"
        />
        <QuickActionCard
          title="Analytiques"
          description="Voir les statistiques"
          icon="ChartBarIcon"
          to="/analytics"
          color="purple"
        />
      </div>
    </div>

    <!-- Stats overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatsCard
        title="Transcriptions"
        :value="stats?.transcriptions?.total || 0"
        :trend="statsLoading ? undefined : 12"
        trend-label="ce mois"
        icon="DocumentTextIcon"
        color="blue"
        :loading="statsLoading"
      />
      <StatsCard
        title="Heures audio"
        :value="formatHours(stats?.usage?.audioHours || 0)"
        :trend="statsLoading ? undefined : 8"
        trend-label="ce mois"
        icon="ClockIcon"
        color="green"
        :loading="statsLoading"
      />
      <StatsCard
        title="Conversations"
        :value="conversationStats?.total || 0"
        :trend="statsLoading ? undefined : 15"
        trend-label="ce mois"
        icon="ChatBubbleLeftRightIcon"
        color="purple"
        :loading="statsLoading"
      />
      <StatsCard
        title="Coût total"
        :value="stats?.usage?.totalCost ? `${stats.usage.totalCost.toFixed(2)}€` : '0€'"
        :trend="statsLoading ? undefined : -5"
        trend-label="ce mois"
        icon="CurrencyEuroIcon"
        color="yellow"
        :loading="statsLoading"
      />
    </div>

    <!-- Main content grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Recent transcriptions -->
      <div class="lg:col-span-2">
        <DashboardCard
          title="Transcriptions récentes"
          :loading="transcriptionsLoading"
        >
          <template #actions>
            <Button
              variant="ghost"
              size="sm"
              @click="$router.push('/transcriptions')"
            >
              Voir tout
            </Button>
          </template>

          <div v-if="recentTranscriptions.length > 0" class="space-y-4">
            <TranscriptionCard
              v-for="transcription in recentTranscriptions"
              :key="transcription.id"
              :transcription="transcription"
              @action="handleTranscriptionAction"
            />
          </div>
          
          <EmptyState
            v-else-if="!transcriptionsLoading"
            title="Aucune transcription"
            description="Commencez par créer votre première transcription."
            action-label="Créer une transcription"
            @action="$router.push('/transcriptions/create')"
          />
        </DashboardCard>
      </div>

      <!-- Right sidebar -->
      <div class="space-y-6">
        <!-- Activity feed -->
        <DashboardCard title="Activité récente" :loading="activityLoading">
          <ActivityFeed :activities="recentActivity" />
        </DashboardCard>

        <!-- Usage chart -->
        <DashboardCard title="Utilisation mensuelle">
          <UsageChart :data="usageData" :loading="chartLoading" />
        </DashboardCard>

        <!-- Quick stats -->
        <DashboardCard title="Statistiques rapides">
          <div class="space-y-3">
            <QuickStat
              label="Temps moyen de traitement"
              :value="formatDuration(stats?.usage?.avgProcessingTime || 0)"
            />
            <QuickStat
              label="Taux de réussite"
              :value="successRate"
            />
            <QuickStat
              label="Dernière activité"
              :value="formatLastActivity(stats?.activity?.lastActivity)"
            />
          </div>
        </DashboardCard>
      </div>
    </div>

    <!-- Welcome modal for new users -->
    <WelcomeModal
      v-if="showWelcomeModal"
      @close="closeWelcomeModal"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { format, formatDistanceToNow } from 'date-fns'
import { fr } from 'date-fns/locale'

// Components
import Button from '@components/ui/Button.vue'
import QuickActionCard from '@components/dashboard/QuickActionCard.vue'
import StatsCard from '@components/dashboard/StatsCard.vue'
import DashboardCard from '@components/dashboard/DashboardCard.vue'
import TranscriptionCard from '@components/transcription/TranscriptionCard.vue'
import ActivityFeed from '@components/dashboard/ActivityFeed.vue'
import UsageChart from '@components/dashboard/UsageChart.vue'
import QuickStat from '@components/dashboard/QuickStat.vue'
import EmptyState from '@components/ui/EmptyState.vue'
import WelcomeModal from '@components/dashboard/WelcomeModal.vue'

// Stores
import { useAuthStore } from '@stores/auth'
import { useUIStore } from '@stores/ui'

// Types
import type { Transcription, UserStats } from '@/types'

// Composables
const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUIStore()

// State
const stats = ref<UserStats | null>(null)
const recentTranscriptions = ref<Transcription[]>([])
const recentActivity = ref<Array<{
  id: string
  type: string
  title: string
  description: string
  timestamp: string
}>>([])
const usageData = ref<Array<{
  date: string
  transcriptions: number
  hours: number
}>>([])
const conversationStats = ref({ total: 0 })

const statsLoading = ref(true)
const transcriptionsLoading = ref(true)
const activityLoading = ref(true)
const chartLoading = ref(true)
const showWelcomeModal = ref(false)

// Computed
const successRate = computed(() => {
  if (!stats.value?.transcriptions) return '0%'
  const { total, completed } = stats.value.transcriptions
  if (total === 0) return '0%'
  return `${Math.round((completed / total) * 100)}%`
})

// Methods
const formatHours = (hours: number) => {
  return `${hours.toFixed(1)}h`
}

const formatDuration = (seconds: number) => {
  if (seconds < 60) return `${seconds}s`
  if (seconds < 3600) return `${Math.round(seconds / 60)}min`
  return `${Math.round(seconds / 3600)}h`
}

const formatLastActivity = (dateString?: string) => {
  if (!dateString) return 'Jamais'
  return formatDistanceToNow(new Date(dateString), { 
    addSuffix: true, 
    locale: fr 
  })
}

const loadDashboardData = async () => {
  try {
    // Load all dashboard data in parallel
    await Promise.all([
      loadStats(),
      loadRecentTranscriptions(),
      loadRecentActivity(),
      loadUsageData(),
      loadConversationStats()
    ])
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
    uiStore.showError('Erreur', 'Impossible de charger les données du tableau de bord')
  }
}

const loadStats = async () => {
  try {
    statsLoading.value = true
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    stats.value = {
      transcriptions: {
        total: 42,
        completed: 38,
        processing: 2,
        failed: 2
      },
      usage: {
        audioHours: 24.5,
        totalCost: 12.50,
        avgProcessingTime: 45
      },
      activity: {
        activeDays: 15,
        lastActivity: new Date().toISOString()
      }
    }
  } finally {
    statsLoading.value = false
  }
}

const loadRecentTranscriptions = async () => {
  try {
    transcriptionsLoading.value = true
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 800))
    
    recentTranscriptions.value = [
      // Mock data
      {
        id: '1',
        status: 'completed',
        language: { code: 'fr', name: 'Français' },
        text: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit...',
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
        audioFile: {
          path: '/uploads/audio1.mp3',
          originalName: 'interview_client.mp3',
          mimeType: 'audio/mpeg',
          size: 1024000,
          duration: 300
        },
        userId: '1'
      }
    ]
  } finally {
    transcriptionsLoading.value = false
  }
}

const loadRecentActivity = async () => {
  try {
    activityLoading.value = true
    await new Promise(resolve => setTimeout(resolve, 600))
    
    recentActivity.value = [
      {
        id: '1',
        type: 'transcription_completed',
        title: 'Transcription terminée',
        description: 'interview_client.mp3',
        timestamp: new Date().toISOString()
      }
    ]
  } finally {
    activityLoading.value = false
  }
}

const loadUsageData = async () => {
  try {
    chartLoading.value = true
    await new Promise(resolve => setTimeout(resolve, 700))
    
    usageData.value = [
      { date: '2024-01', transcriptions: 5, hours: 2.5 },
      { date: '2024-02', transcriptions: 8, hours: 4.2 },
      { date: '2024-03', transcriptions: 12, hours: 6.8 }
    ]
  } finally {
    chartLoading.value = false
  }
}

const loadConversationStats = async () => {
  try {
    await new Promise(resolve => setTimeout(resolve, 500))
    conversationStats.value = { total: 15 }
  } catch (error) {
    console.error('Failed to load conversation stats:', error)
  }
}

const handleTranscriptionAction = (action: string, transcription: Transcription) => {
  switch (action) {
    case 'download':
      // Handle download
      break
    case 'delete':
      // Handle delete
      break
    case 'retry':
      // Handle retry
      break
    default:
      console.log('Unknown action:', action)
  }
}

const closeWelcomeModal = () => {
  showWelcomeModal.value = false
  localStorage.setItem('welcome-modal-seen', 'true')
}

// Lifecycle
onMounted(async () => {
  // Check if user is new
  const hasSeenWelcome = localStorage.getItem('welcome-modal-seen')
  if (!hasSeenWelcome && authStore.user) {
    showWelcomeModal.value = true
  }
  
  // Load dashboard data
  await loadDashboardData()
})
</script>

<script lang="ts">
export default {
  name: 'Dashboard'
}
</script>