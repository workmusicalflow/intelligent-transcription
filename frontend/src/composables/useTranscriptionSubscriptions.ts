import { ref, computed, onUnmounted } from 'vue'
import { useQuery, useSubscription } from '@vue/apollo-composable'
import { gql } from '@apollo/client/core'
import { useGlobalWebSocket } from './useWebSocket'
import { useUIStore } from '@stores/ui'
import type { Transcription, TranscriptionUpdate, TranscriptionProgress } from '@/types'

// GraphQL Subscriptions
const TRANSCRIPTION_UPDATED_SUBSCRIPTION = gql`
  subscription TranscriptionUpdated($transcriptionId: String!) {
    transcriptionUpdated(transcriptionId: $transcriptionId) {
      transcriptionId
      event
      message
      timestamp
      data
    }
  }
`

const TRANSCRIPTION_PROGRESS_SUBSCRIPTION = gql`
  subscription TranscriptionProgress($transcriptionId: String!) {
    transcriptionProgress(transcriptionId: $transcriptionId) {
      transcriptionId
      progress
      stage
      percentage
    }
  }
`

const GET_TRANSCRIPTION_QUERY = gql`
  query GetTranscription($id: ID!) {
    transcription(id: $id) {
      id
      status
      language {
        code
        name
      }
      text
      cost {
        amount
        currency
        formatted
      }
      youtube {
        videoId
        title
        duration
        thumbnail
        originalUrl
      }
      createdAt
      updatedAt
      processingProgress
      audioFile {
        path
        originalName
        mimeType
        size
        duration
        preprocessedPath
      }
      userId
    }
  }
`

interface UseTranscriptionSubscriptionsOptions {
  enableWebSocket?: boolean
  enableGraphQL?: boolean
  enableNotifications?: boolean
}

export function useTranscriptionSubscriptions(
  transcriptionId: string,
  options: UseTranscriptionSubscriptionsOptions = {}
) {
  const {
    enableWebSocket = true,
    enableGraphQL = true,
    enableNotifications = true
  } = options

  const uiStore = useUIStore()
  const webSocket = enableWebSocket ? useGlobalWebSocket() : null
  
  // State
  const currentProgress = ref<TranscriptionProgress | null>(null)
  const lastUpdate = ref<TranscriptionUpdate | null>(null)
  const isSubscribed = ref(false)
  const errors = ref<string[]>([])
  
  // GraphQL Query for current transcription state
  const { result: transcriptionResult, refetch: refetchTranscription, loading: transcriptionLoading } = useQuery(
    GET_TRANSCRIPTION_QUERY,
    { id: transcriptionId },
    {
      enabled: !!transcriptionId,
      errorPolicy: 'all',
      fetchPolicy: 'cache-and-network'
    }
  )
  
  // GraphQL Subscription for transcription updates
  const { onResult: onTranscriptionUpdate, onError: onTranscriptionUpdateError } = useSubscription(
    TRANSCRIPTION_UPDATED_SUBSCRIPTION,
    { transcriptionId },
    {
      enabled: enableGraphQL && !!transcriptionId
    }
  )
  
  // GraphQL Subscription for transcription progress
  const { onResult: onTranscriptionProgress, onError: onTranscriptionProgressError } = useSubscription(
    TRANSCRIPTION_PROGRESS_SUBSCRIPTION,
    { transcriptionId },
    {
      enabled: enableGraphQL && !!transcriptionId
    }
  )
  
  // Computed
  const transcription = computed(() => transcriptionResult.value?.transcription as Transcription | null)
  
  const isProcessing = computed(() => {
    return transcription.value?.status === 'processing'
  })
  
  const progressPercentage = computed(() => {
    return currentProgress.value?.percentage || transcription.value?.processingProgress || 0
  })
  
  const currentStage = computed(() => {
    return currentProgress.value?.stage || 'Initialisation'
  })
  
  // WebSocket event handlers
  let unsubscribeWebSocketUpdates: (() => void) | null = null
  let unsubscribeWebSocketProgress: (() => void) | null = null
  
  const handleWebSocketUpdate = (update: TranscriptionUpdate) => {
    lastUpdate.value = update
    
    // Refetch transcription data when status changes
    if (['completed', 'failed', 'cancelled'].includes(update.event)) {
      refetchTranscription()
    }
    
    // Show notification if enabled
    if (enableNotifications) {
      showUpdateNotification(update)
    }
  }
  
  const handleWebSocketProgress = (progress: TranscriptionProgress) => {
    currentProgress.value = progress
  }
  
  // GraphQL event handlers
  onTranscriptionUpdate((result) => {
    if (result.data?.transcriptionUpdated) {
      handleWebSocketUpdate(result.data.transcriptionUpdated)
    }
  })
  
  onTranscriptionProgress((result) => {
    if (result.data?.transcriptionProgress) {
      handleWebSocketProgress(result.data.transcriptionProgress)
    }
  })
  
  // Error handlers
  onTranscriptionUpdateError((error) => {
    console.error('GraphQL transcription update subscription error:', error)
    errors.value.push(`Subscription error: ${error.message}`)
  })
  
  onTranscriptionProgressError((error) => {
    console.error('GraphQL transcription progress subscription error:', error)
    errors.value.push(`Progress subscription error: ${error.message}`)
  })
  
  // Notification handler
  const showUpdateNotification = (update: TranscriptionUpdate) => {
    const notificationConfig = {
      started: {
        type: 'info' as const,
        title: 'Transcription démarrée',
        message: 'La transcription de votre fichier audio a commencé'
      },
      progress: {
        type: 'info' as const,
        title: 'Transcription en cours',
        message: update.message || 'Traitement en cours...'
      },
      completed: {
        type: 'success' as const,
        title: 'Transcription terminée',
        message: 'Votre transcription est prête!'
      },
      failed: {
        type: 'error' as const,
        title: 'Échec de la transcription',
        message: update.message || 'Une erreur est survenue lors de la transcription'
      }
    }
    
    const config = notificationConfig[update.event]
    if (config) {
      uiStore.showNotification({
        ...config,
        actions: update.event === 'completed' ? [
          {
            label: 'Voir la transcription',
            action: () => {
              // Navigate to transcription detail
              window.location.href = `/transcriptions/${transcriptionId}`
            }
          }
        ] : undefined
      })
    }
  }
  
  // Subscription management
  const subscribe = () => {
    if (isSubscribed.value) return
    
    try {
      // WebSocket subscriptions
      if (webSocket && enableWebSocket) {
        unsubscribeWebSocketUpdates = webSocket.subscribeToTranscriptionUpdates(
          transcriptionId,
          handleWebSocketUpdate
        )
        
        unsubscribeWebSocketProgress = webSocket.subscribeToTranscriptionProgress(
          transcriptionId,
          handleWebSocketProgress
        )
      }
      
      isSubscribed.value = true
      console.log(`Subscribed to transcription updates: ${transcriptionId}`)
      
    } catch (error) {
      console.error('Failed to subscribe to transcription updates:', error)
      errors.value.push(`Subscription failed: ${error instanceof Error ? error.message : 'Unknown error'}`)
    }
  }
  
  const unsubscribe = () => {
    if (!isSubscribed.value) return
    
    try {
      // Cleanup WebSocket subscriptions
      if (unsubscribeWebSocketUpdates) {
        unsubscribeWebSocketUpdates()
        unsubscribeWebSocketUpdates = null
      }
      
      if (unsubscribeWebSocketProgress) {
        unsubscribeWebSocketProgress()
        unsubscribeWebSocketProgress = null
      }
      
      isSubscribed.value = false
      console.log(`Unsubscribed from transcription updates: ${transcriptionId}`)
      
    } catch (error) {
      console.error('Failed to unsubscribe from transcription updates:', error)
    }
  }
  
  // Auto-subscribe if transcriptionId is provided
  if (transcriptionId) {
    subscribe()
  }
  
  // Cleanup on unmount
  onUnmounted(() => {
    unsubscribe()
  })
  
  return {
    // Data
    transcription,
    currentProgress,
    lastUpdate,
    errors,
    
    // State
    isSubscribed,
    isProcessing,
    transcriptionLoading,
    progressPercentage,
    currentStage,
    
    // Methods
    subscribe,
    unsubscribe,
    refetchTranscription,
    
    // Utils
    clearErrors: () => { errors.value = [] }
  }
}

export default useTranscriptionSubscriptions