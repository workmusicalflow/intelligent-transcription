import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@stores/auth'
import { useUIStore } from '@stores/ui'

// Route components (lazy loaded)
const Dashboard = () => import('@views/Dashboard.vue')
const Login = () => import('@views/auth/Login.vue')
const Register = () => import('@views/auth/Register.vue')
const ForgotPassword = () => import('@views/auth/ForgotPassword.vue')
const ResetPassword = () => import('@views/auth/ResetPassword.vue')
const Profile = () => import('@views/profile/Profile.vue')
const Transcriptions = () => import('@views/transcriptions/TranscriptionList.vue')
const TranscriptionDetail = () => import('@views/transcriptions/TranscriptionDetail.vue')
const CreateTranscription = () => import('@views/transcriptions/CreateTranscription.vue')
const Chat = () => import('@views/chat/Chat.vue')
const ChatDetail = () => import('@views/chat/ChatDetail.vue')
const Translations = () => import('@views/translations/Translations.vue')
const Analytics = () => import('@views/analytics/Analytics.vue')
const Settings = () => import('@views/settings/Settings.vue')
const NotFound = () => import('@views/errors/NotFound.vue')
const Unauthorized = () => import('@views/errors/Unauthorized.vue')
const ServerError = () => import('@views/errors/ServerError.vue')

// Admin routes
const AdminDashboard = () => import('@views/admin/Dashboard.vue')
const AdminUsers = () => import('@views/admin/Users.vue')
const AdminSettings = () => import('@views/admin/Settings.vue')

const routes: RouteRecordRaw[] = [
  // Root redirect
  {
    path: '/',
    redirect: (to) => {
      const authStore = useAuthStore()
      return authStore.isAuthenticated ? '/dashboard' : '/login'
    }
  },

  // Auth routes
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: {
      requiresGuest: true,
      layout: 'auth',
      title: 'Connexion'
    }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: {
      requiresGuest: true,
      layout: 'auth',
      title: 'Inscription'
    }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: ForgotPassword,
    meta: {
      requiresGuest: true,
      layout: 'auth',
      title: 'Mot de passe oublié'
    }
  },
  {
    path: '/reset-password/:token',
    name: 'ResetPassword',
    component: ResetPassword,
    meta: {
      requiresGuest: true,
      layout: 'auth',
      title: 'Réinitialiser le mot de passe'
    }
  },

  // Main app routes
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: {
      requiresAuth: true,
      title: 'Tableau de bord',
      transition: 'slide-up'
    }
  },

  // Profile routes
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
    meta: {
      requiresAuth: true,
      title: 'Profil'
    }
  },

  // Transcription routes
  {
    path: '/transcriptions',
    name: 'Transcriptions',
    component: Transcriptions,
    meta: {
      requiresAuth: true,
      title: 'Transcriptions'
    }
  },
  {
    path: '/transcriptions/create',
    name: 'CreateTranscription',
    component: CreateTranscription,
    meta: {
      requiresAuth: true,
      title: 'Nouvelle transcription'
    }
  },
  {
    path: '/transcriptions/:id',
    name: 'TranscriptionDetail',
    component: TranscriptionDetail,
    meta: {
      requiresAuth: true,
      title: 'Détails de la transcription'
    }
  },

  // Chat routes
  {
    path: '/chat',
    name: 'Chat',
    component: Chat,
    meta: {
      requiresAuth: true,
      title: 'Conversations'
    }
  },
  {
    path: '/chat/:id',
    name: 'ChatDetail',
    component: ChatDetail,
    meta: {
      requiresAuth: true,
      title: 'Conversation'
    }
  },

  // Translation routes
  {
    path: '/translations',
    name: 'Translations',
    component: Translations,
    meta: {
      requiresAuth: true,
      title: 'Traductions'
    }
  },

  // Analytics routes
  {
    path: '/analytics',
    name: 'Analytics',
    component: Analytics,
    meta: {
      requiresAuth: true,
      title: 'Analytiques'
    }
  },

  // Settings routes
  {
    path: '/settings',
    name: 'Settings',
    component: Settings,
    meta: {
      requiresAuth: true,
      title: 'Paramètres'
    }
  },

  // Admin routes
  {
    path: '/admin',
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    },
    children: [
      {
        path: '',
        name: 'AdminDashboard',
        component: AdminDashboard,
        meta: {
          title: 'Administration'
        }
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: AdminUsers,
        meta: {
          title: 'Gestion des utilisateurs'
        }
      },
      {
        path: 'settings',
        name: 'AdminSettings',
        component: AdminSettings,
        meta: {
          title: 'Paramètres système'
        }
      }
    ]
  },

  // Error routes
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: Unauthorized,
    meta: {
      title: 'Accès non autorisé'
    }
  },
  {
    path: '/server-error',
    name: 'ServerError',
    component: ServerError,
    meta: {
      title: 'Erreur serveur'
    }
  },

  // 404 route (must be last)
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound,
    meta: {
      title: 'Page non trouvée'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const uiStore = useUIStore()

  // Close sidebar on mobile when navigating
  if (window.innerWidth < 1024) {
    uiStore.closeSidebar()
  }

  // Set loading state
  uiStore.setLoading('navigation', true)

  try {
    // Check if route requires authentication
    if (to.meta.requiresAuth) {
      if (!authStore.isAuthenticated) {
        // Check if user has valid token
        const isValidAuth = await authStore.checkAuth()
        
        if (!isValidAuth) {
          next({
            name: 'Login',
            query: { redirect: to.fullPath }
          })
          return
        }
      }

      // Check admin access
      if (to.meta.requiresAdmin && !authStore.isAdmin) {
        next({ name: 'Unauthorized' })
        return
      }
    }

    // Redirect authenticated users away from guest-only pages
    if (to.meta.requiresGuest && authStore.isAuthenticated) {
      next({ name: 'Dashboard' })
      return
    }

    next()
  } catch (error) {
    console.error('Navigation guard error:', error)
    next({ name: 'ServerError' })
  } finally {
    uiStore.setLoading('navigation', false)
  }
})

// After navigation
router.afterEach((to) => {
  // Set page title
  const baseTitle = 'Intelligent Transcription'
  const pageTitle = to.meta.title as string
  
  if (pageTitle) {
    document.title = `${pageTitle} | ${baseTitle}`
  } else {
    document.title = baseTitle
  }

  // Clear any loading states
  const uiStore = useUIStore()
  uiStore.setLoading('navigation', false)
})

// Error handling
router.onError((error) => {
  console.error('Router error:', error)
  
  const uiStore = useUIStore()
  uiStore.showError(
    'Erreur de navigation',
    'Une erreur est survenue lors de la navigation'
  )
})

export default router

// Route utilities
export const routeUtils = {
  // Check if current route matches
  isCurrentRoute: (routeName: string): boolean => {
    return router.currentRoute.value.name === routeName
  },

  // Get route by name
  getRouteByName: (name: string) => {
    return routes.find(route => route.name === name)
  },

  // Navigate with error handling
  navigateTo: async (to: string | object) => {
    try {
      await router.push(to)
    } catch (error) {
      console.error('Navigation error:', error)
      const uiStore = useUIStore()
      uiStore.showError('Erreur', 'Impossible de naviguer vers cette page')
    }
  },

  // Go back with fallback
  goBack: (fallback = '/dashboard') => {
    if (window.history.length > 1) {
      router.back()
    } else {
      router.push(fallback)
    }
  }
}