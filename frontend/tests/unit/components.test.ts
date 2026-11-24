import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAuctionStore } from '@/stores/auction'
import { useNotificationStore } from '@/stores/notification'
import AuctionCard from '@/components/auction/AuctionCard.vue'
import BidForm from '@/components/auction/BidForm.vue'
import ProductCard from '@/components/product/ProductCard.vue'
import Navbar from '@/components/layout/Navbar.vue'
import { formatCurrency, formatDate, timeRemaining } from '@/utils/formatters'

// Mock router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
    { path: '/login', name: 'login', component: { template: '<div>Login</div>' } },
    { path: '/auctions/:id', name: 'auction-detail', component: { template: '<div>Auction Detail</div>' } },
    { path: '/products/:slug', name: 'product-detail', component: { template: '<div>Product Detail</div>' } },
  ],
})

describe('Utility Functions', () => {
  describe('formatCurrency', () => {
    it('formats USD correctly', () => {
      expect(formatCurrency(1000, 'USD')).toBe('$1,000.00')
      expect(formatCurrency(1000.5, 'USD')).toBe('$1,000.50')
      expect(formatCurrency(0, 'USD')).toBe('$0.00')
    })

    it('formats EUR correctly', () => {
      expect(formatCurrency(1000, 'EUR')).toBe('€1,000.00')
    })

    it('formats GBP correctly', () => {
      expect(formatCurrency(1000, 'GBP')).toBe('£1,000.00')
    })

    it('handles negative numbers', () => {
      expect(formatCurrency(-100, 'USD')).toBe('-$100.00')
    })

    it('handles large numbers', () => {
      expect(formatCurrency(1000000, 'USD')).toBe('$1,000,000.00')
    })
  })

  describe('formatDate', () => {
    it('formats date correctly', () => {
      const date = new Date('2026-01-15T10:30:00Z')
      expect(formatDate(date)).toBe('Jan 15, 2026')
    })

    it('formats with time', () => {
      const date = new Date('2026-01-15T10:30:00Z')
      expect(formatDate(date, true)).toMatch(/Jan 15, 2026.*10:30/)
    })

    it('handles string input', () => {
      expect(formatDate('2026-01-15T10:30:00Z')).toBe('Jan 15, 2026')
    })

    it('handles timestamp', () => {
      expect(formatDate(1736937000000)).toBe('Jan 15, 2026')
    })
  })

  describe('timeRemaining', () => {
    it('returns correct time remaining for future date', () => {
      const future = new Date(Date.now() + 3600000) // 1 hour
      const result = timeRemaining(future)
      expect(result.days).toBe(0)
      expect(result.hours).toBe(1)
      expect(result.minutes).toBeGreaterThanOrEqual(0)
      expect(result.seconds).toBeGreaterThanOrEqual(0)
    })

    it('returns zero for past date', () => {
      const past = new Date(Date.now() - 3600000)
      const result = timeRemaining(past)
      expect(result.days).toBe(0)
      expect(result.hours).toBe(0)
      expect(result.minutes).toBe(0)
      expect(result.seconds).toBe(0)
      expect(result.expired).toBe(true)
    })

    it('returns correct days for distant future', () => {
      const future = new Date(Date.now() + 86400000 * 2) // 2 days
      const result = timeRemaining(future)
      expect(result.days).toBe(2)
    })
  })
})

describe('Pinia Stores', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('useAuthStore', () => {
    it('initializes with empty state', () => {
      const store = useAuthStore()
      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })

    it('sets user and token on login', () => {
      const store = useAuthStore()
      const mockUser = { id: '1', name: 'Test User', email: 'test@example.com', role: 'user' }
      const mockToken = 'mock-jwt-token'

      store.setAuth(mockUser, mockToken)

      expect(store.user).toEqual(mockUser)
      expect(store.token).toBe(mockToken)
      expect(store.isAuthenticated).toBe(true)
    })

    it('clears auth on logout', () => {
      const store = useAuthStore()
      store.setAuth({ id: '1', name: 'Test', email: 'test@test.com', role: 'user' }, 'token')
      store.logout()

      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })

    it('checks user roles correctly', () => {
      const store = useAuthStore()

      store.setAuth({ id: '1', name: 'Admin', email: 'admin@test.com', role: 'admin' }, 'token')
      expect(store.isAdmin).toBe(true)
      expect(store.isSeller).toBe(true)

      store.setAuth({ id: '2', name: 'Seller', email: 'seller@test.com', role: 'seller' }, 'token')
      expect(store.isAdmin).toBe(false)
      expect(store.isSeller).toBe(true)

      store.setAuth({ id: '3', name: 'User', email: 'user@test.com', role: 'user' }, 'token')
      expect(store.isAdmin).toBe(false)
      expect(store.isSeller).toBe(false)
    })
  })

  describe('useAuctionStore', () => {
    it('initializes with empty state', () => {
      const store = useAuctionStore()
      expect(store.auctions).toEqual([])
      expect(store.currentAuction).toBeNull()
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
    })

    it('sets auctions list', () => {
      const store = useAuctionStore()
      const mockAuctions = [
        { id: '1', title: 'Auction 1', current_price: 100 },
        { id: '2', title: 'Auction 2', current_price: 200 },
      ]

      store.setAuctions(mockAuctions)
      expect(store.auctions).toEqual(mockAuctions)
    })

    it('sets current auction', () => {
      const store = useAuctionStore()
      const mockAuction = { id: '1', title: 'Test Auction', current_price: 100 }

      store.setCurrentAuction(mockAuction)
      expect(store.currentAuction).toEqual(mockAuction)
    })

    it('updates auction in list', () => {
      const store = useAuctionStore()
      store.setAuctions([{ id: '1', current_price: 100 }])
      store.updateAuction('1', { current_price: 150 })

      expect(store.auctions[0].current_price).toBe(150)
    })

    it('adds bid to auction', () => {
      const store = useAuctionStore()
      store.setCurrentAuction({ id: '1', bids: [], current_price: 100 })
      store.addBid({ id: 'bid-1', amount: 120, user_id: 'user-1' })

      expect(store.currentAuction.bids).toHaveLength(1)
      expect(store.currentAuction.current_price).toBe(120)
    })

    it('sets loading and error states', () => {
      const store = useAuctionStore()
      store.setLoading(true)
      expect(store.loading).toBe(true)

      store.setError('Something went wrong')
      expect(store.error).toBe('Something went wrong')
    })
  })

  describe('useNotificationStore', () => {
    it('initializes with empty state', () => {
      const store = useNotificationStore()
      expect(store.notifications).toEqual([])
      expect(store.unreadCount).toBe(0)
      expect(store.loading).toBe(false)
    })

    it('adds notification', () => {
      const store = useNotificationStore()
      const notification = { id: '1', title: 'Test', message: 'Message', read_at: null }

      store.addNotification(notification)

      expect(store.notifications).toHaveLength(1)
      expect(store.unreadCount).toBe(1)
    })

    it('marks notification as read', () => {
      const store = useNotificationStore()
      store.addNotification({ id: '1', read_at: null })
      store.addNotification({ id: '2', read_at: null })

      store.markAsRead('1')

      expect(store.notifications.find(n => n.id === '1')?.read_at).not.toBeNull()
      expect(store.unreadCount).toBe(1)
    })

    it('marks all as read', () => {
      const store = useNotificationStore()
      store.addNotification({ id: '1', read_at: null })
      store.addNotification({ id: '2', read_at: null })

      store.markAllAsRead()

      expect(store.unreadCount).toBe(0)
      expect(store.notifications.every(n => n.read_at)).toBe(true)
    })

    it('removes notification', () => {
      const store = useNotificationStore()
      store.addNotification({ id: '1', read_at: null })
      store.addNotification({ id: '2', read_at: null })

      store.removeNotification('1')

      expect(store.notifications).toHaveLength(1)
      expect(store.notifications[0].id).toBe('2')
    })
  })
})

describe('Components', () => {
  let wrapper: VueWrapper<any>

  beforeEach(() => {
    setActivePinia(createPinia())
    const authStore = useAuthStore()
    authStore.setAuth({ id: '1', name: 'Test User', email: 'test@test.com', role: 'user' }, 'token')
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  describe('AuctionCard', () => {
    const mockAuction = {
      id: '1',
      title: 'Test Auction',
      current_price: 100,
      min_bid_increment: 10,
      end_date: new Date(Date.now() + 3600000).toISOString(),
      status: 'active',
      product: {
        title: 'Test Product',
        images: [{ url: '/image.jpg', is_primary: true }],
      },
      total_bids: 5,
    }

    it('renders auction card with correct data', () => {
      wrapper = mount(AuctionCard, {
        props: { auction: mockAuction },
        global: { plugins: [router] },
      })

      expect(wrapper.text()).toContain('Test Auction')
      expect(wrapper.text()).toContain('$100.00')
      expect(wrapper.text()).toContain('5 bids')
    })

    it('shows time remaining', () => {
      wrapper = mount(AuctionCard, {
        props: { auction: mockAuction },
        global: { plugins: [router] },
      })

      expect(wrapper.text()).toMatch(/\d+h \d+m/)
    })

    it('shows correct status badge', () => {
      wrapper = mount(AuctionCard, {
        props: { auction: { ...mockAuction, status: 'active' } },
        global: { plugins: [router] },
      })

      expect(wrapper.find('.status-badge.active').exists()).toBe(true)
    })

    it('navigates to auction detail on click', async () => {
      wrapper = mount(AuctionCard, {
        props: { auction: mockAuction },
        global: { plugins: [router] },
      })

      await wrapper.find('.auction-card').trigger('click')
      expect(router.currentRoute.value.name).toBe('auction-detail')
    })
  })

  describe('BidForm', () => {
    const mockAuction = {
      id: '1',
      current_price: 100,
      min_bid_increment: 10,
      status: 'active',
    }

    it('renders bid form with current price', () => {
      wrapper = mount(BidForm, {
        props: { auction: mockAuction },
        global: { plugins: [router] },
      })

      expect(wrapper.text()).toContain('Current Price')
      expect(wrapper.text()).toContain('$100.00')
      expect(wrapper.text()).toContain('Minimum Bid')
      expect(wrapper.text()).toContain('$110.00')
    })

    it('shows validation error for bid below minimum', async () => {
      wrapper = mount(BidForm, {
        props: { auction: mockAuction },
        global: { plugins: [router] },
      })

      const input = wrapper.find('input[type="number"]')
      await input.setValue(105)
      await wrapper.find('form').trigger('submit.prevent')

      expect(wrapper.text()).toContain('Bid must be at least')
    })

    it('shows validation error for non-numeric input', async () => {
      wrapper = mount(BidForm, {
        props: { mockAuction },
        global: { plugins: [router] },
      })

      const input = wrapper.find('input[type="number"]')
      await input.setValue('abc')
      await wrapper.find('form').trigger('submit.prevent')

      expect(wrapper.text()).toContain('Please enter a valid amount')
    })

    it('emits bid-placed event on valid submit', async () => {
      wrapper = mount(BidForm, {
        props: { auction: mockAuction },
        global: { plugins: [router] },
      })

      const input = wrapper.find('input[type="number"]')
      await input.setValue(120)
      await wrapper.find('form').trigger('submit.prevent')

      expect(wrapper.emitted('bid-placed')).toBeTruthy()
      expect(wrapper.emitted('bid-placed')?.[0]).toEqual([120])
    })

    it('disables submit when auction not active', () => {
      wrapper = mount(BidForm, {
        props: { auction: { ...mockAuction, status: 'ended' } },
        global: { plugins: [router] },
      })

      expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()
    })
  })

  describe('ProductCard', () => {
    const mockProduct = {
      id: '1',
      title: 'Test Product',
      slug: 'test-product',
      starting_price: 100,
      condition: 'new',
      status: 'published',
      images: [{ url: '/image.jpg', is_primary: true }],
      category: { name: 'Electronics', slug: 'electronics' },
    }

    it('renders product card with correct data', () => {
      wrapper = mount(ProductCard, {
        props: { product: mockProduct },
        global: { plugins: [router] },
      })

      expect(wrapper.text()).toContain('Test Product')
      expect(wrapper.text()).toContain('$100.00')
      expect(wrapper.text()).toContain('New')
      expect(wrapper.text()).toContain('Electronics')
    })

    it('shows primary image', () => {
      wrapper = mount(ProductCard, {
        props: { product: mockProduct },
        global: { plugins: [router] },
      })

      const img = wrapper.find('img')
      expect(img.attributes('src')).toBe('/image.jpg')
    })

    it('navigates to product detail on click', async () => {
      wrapper = mount(ProductCard, {
        props: { product: mockProduct },
        global: { plugins: [router] },
      })

      await wrapper.find('.product-card').trigger('click')
      expect(router.currentRoute.value.name).toBe('product-detail')
    })
  })

  describe('Navbar', () => {
    it('renders logo and navigation links', () => {
      wrapper = mount(Navbar, {
        global: { plugins: [router] },
      })

      expect(wrapper.text()).toContain('AuctionHub')
      expect(wrapper.find('a[href="/"]').exists()).toBe(true)
      expect(wrapper.find('a[href="/auctions"]').exists()).toBe(true)
      expect(wrapper.find('a[href="/products"]').exists()).toBe(true)
    })

    it('shows user menu when authenticated', () => {
      wrapper = mount(Navbar, {
        global: { plugins: [router] },
      })

      expect(wrapper.find('.user-menu').exists()).toBe(true)
      expect(wrapper.text()).toContain('Test User')
    })

    it('shows login/register when not authenticated', () => {
      const authStore = useAuthStore()
      authStore.logout()

      wrapper = mount(Navbar, {
        global: { plugins: [router] },
      })

      expect(wrapper.find('a[href="/login"]').exists()).toBe(true)
      expect(wrapper.find('a[href="/register"]').exists()).toBe(true)
    })

    it('shows seller dashboard link for sellers', () => {
      const authStore = useAuthStore()
      authStore.setAuth({ id: '1', name: 'Seller', email: 'seller@test.com', role: 'seller' }, 'token')

      wrapper = mount(Navbar, {
        global: { plugins: [router] },
      })

      expect(wrapper.find('a[href="/seller/dashboard"]').exists()).toBe(true)
    })
  })
})