import { vi } from 'vitest'
import { config } from '@vue/test-utils'

// Mock router
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn(),
    replace: vi.fn(),
    go: vi.fn(),
    back: vi.fn(),
    forward: vi.fn(),
    currentRoute: { value: { name: 'home', params: {}, query: {} } },
  }),
  useRoute: () => ({
    params: {},
    query: {},
    name: 'home',
  }),
}))

// Mock Pinia stores
vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    user: null,
    token: null,
    isAuthenticated: false,
    isAdmin: false,
    isSeller: false,
    setAuth: vi.fn(),
    logout: vi.fn(),
  }),
}))

vi.mock('@/stores/auction', () => ({
  useAuctionStore: () => ({
    auctions: [],
    currentAuction: null,
    loading: false,
    error: null,
    setAuctions: vi.fn(),
    setCurrentAuction: vi.fn(),
    updateAuction: vi.fn(),
    addBid: vi.fn(),
    setLoading: vi.fn(),
    setError: vi.fn(),
  }),
}))

vi.mock('@/stores/notification', () => ({
  useNotificationStore: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
    addNotification: vi.fn(),
    markAsRead: vi.fn(),
    markAllAsRead: vi.fn(),
    removeNotification: vi.fn(),
  }),
}))

// Mock axios
vi.mock('axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    create: vi.fn(() => ({
      get: vi.fn(),
      post: vi.fn(),
      put: vi.fn(),
      delete: vi.fn(),
      interceptors: {
        request: { use: vi.fn() },
        response: { use: vi.fn() },
      },
    })),
  },
}))

// Mock Laravel Echo
vi.mock('laravel-echo', () => ({
  default: vi.fn().mockImplementation(() => ({
    private: vi.fn().mockReturnThis(),
    channel: vi.fn().mockReturnThis(),
    listen: vi.fn().mockReturnThis(),
    leave: vi.fn(),
    disconnect: vi.fn(),
  })),
}))

// Mock Pusher
vi.mock('pusher-js', () => ({
  default: vi.fn().mockImplementation(() => ({
    subscribe: vi.fn().mockReturnThis(),
    bind: vi.fn().mockReturnThis(),
    unbind: vi.fn(),
    disconnect: vi.fn(),
  })),
}))

// Global test config
config.global.stubs = {
  Teleport: true,
  Transition: true,
  TransitionGroup: true,
}

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Mock ResizeObserver
global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))

// Mock IntersectionObserver
global.IntersectionObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))

// Suppress console.error in tests (optional)
const originalError = console.error
beforeAll(() => {
  console.error = (...args) => {
    if (args[0]?.includes?.('Warning: JIT is incompatible')) return
    originalError.apply(console, args)
  }
})

afterAll(() => {
  console.error = originalError
})