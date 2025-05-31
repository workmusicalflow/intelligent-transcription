// Mock Chat API for tests
export const ChatAPI = {
  getConversations: () => Promise.resolve({
    success: true,
    data: []
  }),

  createConversation: () => Promise.resolve({
    success: true,
    data: {
      id: 'mock-conversation-id',
      title: 'Mock Conversation',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      messageCount: 0
    }
  }),

  getMessages: () => Promise.resolve({
    success: true,
    data: {
      messages: []
    }
  }),

  sendMessage: () => Promise.resolve({
    success: true,
    data: {
      id: 'mock-message-id',
      conversationId: 'mock-conversation-id',
      content: 'Mock message',
      role: 'user' as const,
      timestamp: new Date().toISOString()
    }
  }),

  deleteConversation: () => Promise.resolve({
    success: true,
    data: null
  })
}