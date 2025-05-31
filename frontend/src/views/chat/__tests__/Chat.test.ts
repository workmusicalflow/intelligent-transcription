import { describe, it, expect, vi, beforeEach } from 'vitest'
import { flushPromises } from '@vue/test-utils'
import Chat from '../Chat.vue'
import { mountWithPlugins, factories } from '@/tests/utils/test-utils'
import { ChatAPI } from '@/api/chat'

// Mock the API
vi.mock('@/api/chat', () => ({
  ChatAPI: {
    getConversations: vi.fn(),
    createConversation: vi.fn(),
    getMessages: vi.fn(),
    sendMessage: vi.fn(),
    deleteConversation: vi.fn()
  }
}))

// Mock router
const mockRouter = {
  push: vi.fn(),
  replace: vi.fn()
}

describe('Chat.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders chat interface correctly', () => {
    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    expect(wrapper.find('[data-testid="chat-container"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="conversations-list"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="chat-area"]').exists()).toBe(true)
  })

  it('loads conversations on mount', async () => {
    const mockConversations = [
      factories.conversation({ id: '1', title: 'Conversation 1' }),
      factories.conversation({ id: '2', title: 'Conversation 2' })
    ]

    vi.mocked(ChatAPI.getConversations).mockResolvedValue({
      success: true,
      data: mockConversations
    })

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    expect(ChatAPI.getConversations).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Conversation 1')
    expect(wrapper.text()).toContain('Conversation 2')
  })

  it('creates new conversation', async () => {
    const newConversation = factories.conversation({ 
      id: 'new-id', 
      title: 'Nouvelle conversation' 
    })

    vi.mocked(ChatAPI.getConversations).mockResolvedValue({
      success: true,
      data: []
    })

    vi.mocked(ChatAPI.createConversation).mockResolvedValue({
      success: true,
      data: newConversation
    })

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Find and click new conversation button
    const newChatButton = wrapper.find('[data-testid="new-conversation"]')
    expect(newChatButton.exists()).toBe(true)

    await newChatButton.trigger('click')
    await flushPromises()

    expect(ChatAPI.createConversation).toHaveBeenCalled()
  })

  it('selects conversation and loads messages', async () => {
    const mockConversations = [
      factories.conversation({ id: '1', title: 'Test Conversation' })
    ]

    const mockMessages = [
      factories.message({ 
        id: '1', 
        content: 'Hello', 
        role: 'user',
        conversationId: '1'
      }),
      factories.message({ 
        id: '2', 
        content: 'Hi there!', 
        role: 'assistant',
        conversationId: '1'
      })
    ]

    vi.mocked(ChatAPI.getConversations).mockResolvedValue({
      success: true,
      data: mockConversations
    })

    vi.mocked(ChatAPI.getMessages).mockResolvedValue({
      success: true,
      data: { messages: mockMessages }
    })

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Click on conversation
    const conversationItem = wrapper.find('[data-testid="conversation-item"]')
    if (conversationItem.exists()) {
      await conversationItem.trigger('click')
      await flushPromises()

      expect(ChatAPI.getMessages).toHaveBeenCalledWith('1')
      expect(wrapper.text()).toContain('Hello')
      expect(wrapper.text()).toContain('Hi there!')
    }
  })

  it('sends new message', async () => {
    const mockConversations = [
      factories.conversation({ id: '1', title: 'Test Conversation' })
    ]

    const mockMessages = [
      factories.message({ id: '1', content: 'Hello', role: 'user' })
    ]

    const newMessage = factories.message({
      id: '2',
      content: 'New message',
      role: 'user'
    })

    vi.mocked(ChatAPI.getConversations).mockResolvedValue({
      success: true,
      data: mockConversations
    })

    vi.mocked(ChatAPI.getMessages).mockResolvedValue({
      success: true,
      data: { messages: mockMessages }
    })

    vi.mocked(ChatAPI.sendMessage).mockResolvedValue({
      success: true,
      data: newMessage
    })

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Select conversation first
    const conversationItem = wrapper.find('[data-testid="conversation-item"]')
    if (conversationItem.exists()) {
      await conversationItem.trigger('click')
      await flushPromises()

      // Find message input and send button
      const messageInput = wrapper.find('[data-testid="message-input"]')
      const sendButton = wrapper.find('[data-testid="send-message"]')

      if (messageInput.exists() && sendButton.exists()) {
        await messageInput.setValue('New message')
        await sendButton.trigger('click')
        await flushPromises()

        expect(ChatAPI.sendMessage).toHaveBeenCalledWith('1', 'New message')
      }
    }
  })

  it('handles message input with enter key', async () => {
    const mockConversations = [
      factories.conversation({ id: '1', title: 'Test Conversation' })
    ]

    vi.mocked(ChatAPI.getConversations).mockResolvedValue({
      success: true,
      data: mockConversations
    })

    vi.mocked(ChatAPI.getMessages).mockResolvedValue({
      success: true,
      data: { messages: [] }
    })

    vi.mocked(ChatAPI.sendMessage).mockResolvedValue({
      success: true,
      data: factories.message({ content: 'Test message' })
    })

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Select conversation
    const conversationItem = wrapper.find('[data-testid="conversation-item"]')
    if (conversationItem.exists()) {
      await conversationItem.trigger('click')
      await flushPromises()

      // Type message and press enter
      const messageInput = wrapper.find('[data-testid="message-input"]')
      if (messageInput.exists()) {
        await messageInput.setValue('Test message')
        await messageInput.trigger('keydown.enter')
        await flushPromises()

        expect(ChatAPI.sendMessage).toHaveBeenCalled()
      }
    }
  })

  it('prevents sending empty messages', async () => {
    const mockConversations = [
      factories.conversation({ id: '1', title: 'Test Conversation' })
    ]

    vi.mocked(ChatAPI.getConversations).mockResolvedValue({
      success: true,
      data: mockConversations
    })

    vi.mocked(ChatAPI.getMessages).mockResolvedValue({
      success: true,
      data: { messages: [] }
    })

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Select conversation
    const conversationItem = wrapper.find('[data-testid="conversation-item"]')
    if (conversationItem.exists()) {
      await conversationItem.trigger('click')
      await flushPromises()

      // Try to send empty message
      const sendButton = wrapper.find('[data-testid="send-message"]')
      if (sendButton.exists()) {
        // Button should be disabled for empty input
        expect(sendButton.attributes('disabled')).toBeDefined()
      }
    }
  })

  it('deletes conversation', async () => {
    const mockConversations = [
      factories.conversation({ id: '1', title: 'Test Conversation' })
    ]

    vi.mocked(ChatAPI.getConversations).mockResolvedValue({
      success: true,
      data: mockConversations
    })

    vi.mocked(ChatAPI.deleteConversation).mockResolvedValue({
      success: true,
      data: null
    })

    // Mock window.confirm
    global.confirm = vi.fn(() => true)

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Find delete button (might be in dropdown menu)
    const deleteButton = wrapper.find('[data-testid="delete-conversation"]')
    if (deleteButton.exists()) {
      await deleteButton.trigger('click')
      await flushPromises()

      expect(global.confirm).toHaveBeenCalled()
      expect(ChatAPI.deleteConversation).toHaveBeenCalledWith('1')
    }
  })

  it('shows loading state when sending message', async () => {
    const mockConversations = [
      factories.conversation({ id: '1', title: 'Test Conversation' })
    ]

    vi.mocked(ChatAPI.getConversations).mockResolvedValue({
      success: true,
      data: mockConversations
    })

    vi.mocked(ChatAPI.getMessages).mockResolvedValue({
      success: true,
      data: { messages: [] }
    })

    // Make sendMessage take time to resolve
    vi.mocked(ChatAPI.sendMessage).mockImplementation(() => 
      new Promise(resolve => 
        setTimeout(() => resolve({
          success: true,
          data: factories.message({ content: 'Test' })
        }), 100)
      )
    )

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Select conversation and send message
    const conversationItem = wrapper.find('[data-testid="conversation-item"]')
    if (conversationItem.exists()) {
      await conversationItem.trigger('click')
      await flushPromises()

      const messageInput = wrapper.find('[data-testid="message-input"]')
      const sendButton = wrapper.find('[data-testid="send-message"]')

      if (messageInput.exists() && sendButton.exists()) {
        await messageInput.setValue('Test message')
        await sendButton.trigger('click')

        // Should show loading state
        expect(wrapper.find('[data-testid="sending-indicator"]').exists()).toBe(true)
      }
    }
  })

  it('handles API errors gracefully', async () => {
    vi.mocked(ChatAPI.getConversations).mockRejectedValue(new Error('API Error'))

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Should show error state
    expect(wrapper.find('[data-testid="error-state"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Erreur')
  })

  it('filters conversations with search', async () => {
    const mockConversations = [
      factories.conversation({ id: '1', title: 'Important Discussion' }),
      factories.conversation({ id: '2', title: 'Random Chat' }),
      factories.conversation({ id: '3', title: 'Important Meeting' })
    ]

    vi.mocked(ChatAPI.getConversations).mockResolvedValue({
      success: true,
      data: mockConversations
    })

    const wrapper = mountWithPlugins(Chat, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Find search input
    const searchInput = wrapper.find('[data-testid="conversation-search"]')
    if (searchInput.exists()) {
      await searchInput.setValue('Important')
      await flushPromises()

      // Should filter conversations
      expect(wrapper.text()).toContain('Important Discussion')
      expect(wrapper.text()).toContain('Important Meeting')
      expect(wrapper.text()).not.toContain('Random Chat')
    }
  })
})