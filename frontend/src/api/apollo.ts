import { ApolloClient, InMemoryCache, createHttpLink, split, from } from '@apollo/client/core'
import { setContext } from '@apollo/client/link/context'
import { onError } from '@apollo/client/link/error'
import { createClient } from 'graphql-ws'
import { GraphQLWsLink } from '@apollo/client/link/subscriptions'
import { getMainDefinition } from '@apollo/client/utilities'
import type { ApolloLink } from '@apollo/client/core'
import type { 
  OperationDefinitionNode,
  FieldNode,
  FragmentDefinitionNode,
  InlineFragmentNode,
  ExecutionResult
} from 'graphql'

// HTTP Link
const httpLink = createHttpLink({
  uri: import.meta.env.VITE_GRAPHQL_ENDPOINT || '/graphql'
})

// WebSocket Link for subscriptions
const wsClient = createClient({
  url: `ws${location.protocol === 'https:' ? 's' : ''}://${location.host}/graphql`,
  connectionParams: () => {
    const token = localStorage.getItem('auth-token')
    return token ? { Authorization: `Bearer ${token}` } : {}
  },
  retryAttempts: 5,
  on: {
    connected: () => console.log('WebSocket connected'),
    error: (error) => console.error('WebSocket error:', error)
  }
})

const wsLink = new GraphQLWsLink(wsClient)

// Auth Link
const authLink = setContext((_, { headers = {} }: { headers?: Record<string, any> }) => {
  const token = localStorage.getItem('auth-token')
  
  return {
    headers: {
      ...headers,
      authorization: token ? `Bearer ${token}` : ''
    }
  }
})

// Error Link
const errorLink = onError(({ graphQLErrors, networkError }) => {
  if (graphQLErrors) {
    graphQLErrors.forEach(({ message, locations, path, extensions }) => {
      console.error(`GraphQL error: Message: ${message}, Location: ${locations}, Path: ${path}`)
      
      // Handle specific error types
      if (extensions?.code === 'UNAUTHENTICATED') {
        // Redirect to login
        localStorage.removeItem('auth-token')
        window.location.href = '/login'
      }
    })
  }

  if (networkError) {
    console.error(`Network error: ${networkError}`)
    
    // Handle network errors
    if ('statusCode' in networkError) {
      switch (networkError.statusCode) {
        case 401:
          localStorage.removeItem('auth-token')
          window.location.href = '/login'
          break
        case 403:
          console.error('Access forbidden')
          break
        case 500:
          console.error('Server error')
          break
      }
    }
  }
})

// Split link for HTTP vs WebSocket
const splitLink = split(
  ({ query }) => {
    const definition = getMainDefinition(query)
    return (
      definition.kind === 'OperationDefinition' &&
      definition.operation === 'subscription'
    )
  },
  wsLink,
  from([authLink, httpLink])
)

// Apollo Client
export const apolloClient = new ApolloClient({
  link: from([errorLink, splitLink]),
  cache: new InMemoryCache({
    typePolicies: {
      Query: {
        fields: {
          transcriptions: {
            // Merge function for pagination
            keyArgs: false,
            merge(existing = { data: [] }, incoming) {
              return {
                ...incoming,
                data: [...existing.data, ...incoming.data]
              }
            }
          },
          conversations: {
            keyArgs: false,
            merge(existing = { data: [] }, incoming) {
              return {
                ...incoming,
                data: [...existing.data, ...incoming.data]
              }
            }
          }
        }
      },
      Transcription: {
        fields: {
          processingProgress: {
            merge: false // Always use incoming value
          }
        }
      }
    }
  }),
  defaultOptions: {
    watchQuery: {
      errorPolicy: 'all',
      fetchPolicy: 'cache-and-network'
    },
    query: {
      errorPolicy: 'all',
      fetchPolicy: 'cache-first'
    },
    mutate: {
      errorPolicy: 'all'
    }
  },
  connectToDevTools: import.meta.env.DEV
})

// Helper functions
export const resetApolloCache = () => {
  return apolloClient.clearStore()
}

export const refetchQueries = (queries: string[]) => {
  return apolloClient.refetchQueries({
    include: queries
  })
}

// Connection management
export const apolloConnection = {
  connect: () => {
    // Connection is automatic
    console.log('Apollo client connected')
  },
  
  disconnect: () => {
    wsClient.dispose()
    console.log('Apollo client disconnected')
  },
  
  reconnect: () => {
    wsClient.dispose()
    // Create new client...
    console.log('Apollo client reconnecting')
  }
}

export default apolloClient