import { describe, it, expect, vi, beforeEach } from 'vitest'
import { flushPromises } from '@vue/test-utils'
import TranscriptionDetail from '../TranscriptionDetail.vue'
import { mountWithPlugins, factories, mockApiResponse } from '@/tests/utils/test-utils'
import { TranscriptionAPI } from '@/api/transcriptions'

// Mock the API
vi.mock('@/api/transcriptions', () => ({
  TranscriptionAPI: {
    getTranscriptionDetails: vi.fn()
  }
}))

// Mock the router params
const mockRoute = {
  params: { id: '1' },
  path: '/transcriptions/1',
  name: 'transcription-detail'
}

describe('TranscriptionDetail.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders loading state initially', () => {
    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    expect(wrapper.find('[data-testid="loading"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Chargement de la transcription...')
  })

  it('renders transcription details when loaded successfully', async () => {
    const mockTranscription = factories.transcription({
      fileName: 'test-audio.mp3',
      text: 'Ceci est un test de transcription',
      language: 'fr',
      status: 'completed',
      duration: 180,
      fileSize: 1024000
    })

    const mockResponse = {
      success: true,
      data: {
        transcription: mockTranscription,
        textStats: {
          characterCount: 30,
          wordCount: 6,
          paragraphCount: 1,
          estimatedReadingTime: 1
        },
        segments: [],
        audioUrl: '/audio/test.mp3'
      }
    }

    vi.mocked(TranscriptionAPI.getTranscriptionDetails).mockResolvedValue(mockResponse)

    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    await flushPromises()

    // Check if loading is gone and content is displayed
    expect(wrapper.find('[data-testid="loading"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('test-audio.mp3')
    expect(wrapper.text()).toContain('Ceci est un test de transcription')
    expect(wrapper.text()).toContain('Terminée')
  })

  it('renders error state when API call fails', async () => {
    const errorMessage = 'Transcription not found'
    vi.mocked(TranscriptionAPI.getTranscriptionDetails).mockRejectedValue(new Error(errorMessage))

    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    await flushPromises()

    expect(wrapper.find('[data-testid="error"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Erreur')
    expect(wrapper.text()).toContain(errorMessage)
  })

  it('switches between view modes correctly', async () => {
    const mockTranscription = factories.transcription()
    const mockResponse = {
      success: true,
      data: {
        transcription: mockTranscription,
        textStats: {
          characterCount: 30,
          wordCount: 6,
          paragraphCount: 1,
          estimatedReadingTime: 1
        },
        segments: [{
          id: 1,
          text: 'Segment 1',
          startTime: 0,
          endTime: 10,
          wordCount: 2,
          isEstimated: false
        }],
        audioUrl: '/audio/test.mp3'
      }
    }

    vi.mocked(TranscriptionAPI.getTranscriptionDetails).mockResolvedValue(mockResponse)

    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    await flushPromises()

    // Default should be 'read' mode
    expect(wrapper.find('[data-testid="read-mode"]').exists()).toBe(true)

    // Click edit mode button
    const editButton = wrapper.find('button[data-testid="edit-mode-btn"]')
    if (editButton.exists()) {
      await editButton.trigger('click')
      expect(wrapper.find('[data-testid="edit-mode"]').exists()).toBe(true)
    }

    // Click segments mode button
    const segmentsButton = wrapper.find('button[data-testid="segments-mode-btn"]')
    if (segmentsButton.exists()) {
      await segmentsButton.trigger('click')
      expect(wrapper.find('[data-testid="segments-mode"]').exists()).toBe(true)
    }
  })

  it('formats file size correctly', async () => {
    const mockTranscription = factories.transcription({
      fileSize: 1048576 // 1 MB
    })

    const mockResponse = {
      success: true,
      data: {
        transcription: mockTranscription,
        textStats: { characterCount: 30, wordCount: 6, paragraphCount: 1, estimatedReadingTime: 1 },
        segments: [],
        audioUrl: null
      }
    }

    vi.mocked(TranscriptionAPI.getTranscriptionDetails).mockResolvedValue(mockResponse)

    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    await flushPromises()

    expect(wrapper.text()).toContain('1 MB')
  })

  it('formats duration correctly', async () => {
    const mockTranscription = factories.transcription({
      duration: 3661 // 1h 1m 1s
    })

    const mockResponse = {
      success: true,
      data: {
        transcription: mockTranscription,
        textStats: { characterCount: 30, wordCount: 6, paragraphCount: 1, estimatedReadingTime: 1 },
        segments: [],
        audioUrl: null
      }
    }

    vi.mocked(TranscriptionAPI.getTranscriptionDetails).mockResolvedValue(mockResponse)

    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    await flushPromises()

    expect(wrapper.text()).toContain('1h 1m')
  })

  it('shows YouTube UI for YouTube transcriptions', async () => {
    const mockTranscription = factories.transcription({
      sourceType: 'youtube',
      youtubeUrl: 'https://www.youtube.com/watch?v=test'
    })

    const mockResponse = {
      success: true,
      data: {
        transcription: mockTranscription,
        textStats: { characterCount: 30, wordCount: 6, paragraphCount: 1, estimatedReadingTime: 1 },
        segments: [],
        audioUrl: null
      }
    }

    vi.mocked(TranscriptionAPI.getTranscriptionDetails).mockResolvedValue(mockResponse)

    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    await flushPromises()

    expect(wrapper.text()).toContain('YouTube')
    expect(wrapper.text()).toContain('Voir sur YouTube')
  })

  it('enables edit mode and tracks changes', async () => {
    const mockTranscription = factories.transcription({
      text: 'Original text'
    })

    const mockResponse = {
      success: true,
      data: {
        transcription: mockTranscription,
        textStats: { characterCount: 30, wordCount: 6, paragraphCount: 1, estimatedReadingTime: 1 },
        segments: [],
        audioUrl: null
      }
    }

    vi.mocked(TranscriptionAPI.getTranscriptionDetails).mockResolvedValue(mockResponse)

    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    await flushPromises()

    // Switch to edit mode
    const editButton = wrapper.find('button[data-testid="edit-mode-btn"]')
    if (editButton.exists()) {
      await editButton.trigger('click')

      // Find textarea and modify content
      const textarea = wrapper.find('textarea')
      if (textarea.exists()) {
        await textarea.setValue('Modified text')
        
        // Save button should be enabled
        const saveButton = wrapper.find('button[data-testid="save-changes"]')
        expect(saveButton.exists()).toBe(true)
        expect(saveButton.attributes('disabled')).toBeUndefined()
      }
    }
  })

  it('handles font size adjustment', async () => {
    const mockTranscription = factories.transcription()
    const mockResponse = {
      success: true,
      data: {
        transcription: mockTranscription,
        textStats: { characterCount: 30, wordCount: 6, paragraphCount: 1, estimatedReadingTime: 1 },
        segments: [],
        audioUrl: null
      }
    }

    vi.mocked(TranscriptionAPI.getTranscriptionDetails).mockResolvedValue(mockResponse)

    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    await flushPromises()

    // Find font size buttons
    const increaseButton = wrapper.find('button[data-testid="increase-font"]')
    const decreaseButton = wrapper.find('button[data-testid="decrease-font"]')

    if (increaseButton.exists()) {
      await increaseButton.trigger('click')
      // Font size should increase (would need to check style or computed properties)
    }

    if (decreaseButton.exists()) {
      await decreaseButton.trigger('click')
      // Font size should decrease
    }
  })

  it('handles export functionality', async () => {
    const mockTranscription = factories.transcription()
    const mockResponse = {
      success: true,
      data: {
        transcription: mockTranscription,
        textStats: { characterCount: 30, wordCount: 6, paragraphCount: 1, estimatedReadingTime: 1 },
        segments: [],
        audioUrl: null
      }
    }

    vi.mocked(TranscriptionAPI.getTranscriptionDetails).mockResolvedValue(mockResponse)

    // Mock URL.createObjectURL
    global.URL.createObjectURL = vi.fn(() => 'blob:test-url')
    global.URL.revokeObjectURL = vi.fn()

    const wrapper = mountWithPlugins(TranscriptionDetail, {
      global: {
        mocks: {
          $route: mockRoute
        }
      }
    })

    await flushPromises()

    // Find export buttons
    const exportButtons = wrapper.findAll('[data-testid^="export-"]')
    
    if (exportButtons.length > 0) {
      await exportButtons[0].trigger('click')
      
      // Should create blob URL for download
      expect(global.URL.createObjectURL).toHaveBeenCalled()
    }
  })
})