import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
    meta: { title: 'Auction Marketplace' },
  },
  {
    path: '/auctions',
    name: 'auctions',
    component: () => import('@/views/auctions/AuctionsView.vue'),
    meta: { title: 'Browse Auctions' },
  },
  {
    path: '/auctions/:id',
    name: 'auction-detail',
    component: () => import('@/views/auctions/AuctionDetailView.vue'),
    meta: { title: 'Auction Details' },
    props: true,
  },
  {
    path: '/products/:id',
    name: 'product-detail',
    component: () => import('@/views/products/ProductDetailView.vue'),
    meta: { title: 'Product Details' },
    props: true,
  },
  {
    path: '/categories',
    name: 'categories',
    component: () => import('@/views/categories/CategoriesView.vue'),
    meta: { title: 'Categories' },
  },
  {
    path: '/categories/:slug',
    name: 'category-detail',
    component: () => import('@/views/categories/CategoryDetailView.vue'),
    meta: { title: 'Category' },
    props: true,
  },

  // Auth pages
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { title: 'Login', guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { title: 'Register', guest: true },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/auth/ForgotPasswordView.vue'),
    meta: { title: 'Forgot Password', guest: true },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/auth/ResetPasswordView.vue'),
    meta: { title: 'Reset Password', guest: true },
  },

  // Protected user pages
  {
    path: '/dashboard',
    component: () => import('@/layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/dashboard/OverviewView.vue'),
        meta: { title: 'Dashboard' },
      },
      {
        path: 'products',
        name: 'my-products',
        component: () => import('@/views/dashboard/MyProductsView.vue'),
        meta: { title: 'My Products' },
      },
      {
        path: 'products/create',
        name: 'create-product',
        component: () => import('@/views/dashboard/CreateProductView.vue'),
        meta: { title: 'Create Product' },
      },
      {
        path: 'products/:id/edit',
        name: 'edit-product',
        component: () => import('@/views/dashboard/EditProductView.vue'),
        meta: { title: 'Edit Product' },
        props: true,
      },
      {
        path: 'auctions',
        name: 'my-auctions',
        component: () => import('@/views/dashboard/MyAuctionsView.vue'),
        meta: { title: 'My Auctions' },
      },
      {
        path: 'auctions/create',
        name: 'create-auction',
        component: () => import('@/views/dashboard/CreateAuctionView.vue'),
        meta: { title: 'Create Auction' },
      },
      {
        path: 'participated',
        name: 'participated-auctions',
        component: () => import('@/views/dashboard/ParticipatedAuctionsView.vue'),
        meta: { title: 'Participated Auctions' },
      },
      {
        path: 'won',
        name: 'won-auctions',
        component: () => import('@/views/dashboard/WonAuctionsView.vue'),
        meta: { title: 'Won Auctions' },
      },
      {
        path: 'history',
        name: 'auction-history',
        component: () => import('@/views/dashboard/AuctionHistoryView.vue'),
        meta: { title: 'Auction History' },
      },
      {
        path: 'bids',
        name: 'my-bids',
        component: () => import('@/views/dashboard/MyBidsView.vue'),
        meta: { title: 'My Bids' },
      },
      {
        path: 'profile',
        name: 'profile',
        component: () => import('@/views/dashboard/ProfileView.vue'),
        meta: { title: 'Profile Settings' },
      },
    ],
  },

  // Admin pages
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        name: 'admin-dashboard',
        component: () => import('@/views/admin/DashboardView.vue'),
        meta: { title: 'Admin Dashboard' },
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('@/views/admin/UsersView.vue'),
        meta: { title: 'User Management' },
      },
      {
        path: 'products',
        name: 'admin-products',
        component: () => import('@/views/admin/ProductsView.vue'),
        meta: { title: 'Product Management' },
      },
      {
        path: 'auctions',
        name: 'admin-auctions',
        component: () => import('@/views/admin/AuctionsView.vue'),
        meta: { title: 'Auction Management' },
      },
      {
        path: 'categories',
        name: 'admin-categories',
        component: () => import('@/views/admin/CategoriesView.vue'),
        meta: { title: 'Category Management' },
      },
      {
        path: 'reports',
        name: 'admin-reports',
        component: () => import('@/views/admin/ReportsView.vue'),
        meta: { title: 'Reports & Statistics' },
      },
    ],
  },

  // 404
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { title: 'Page Not Found' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  },
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Set page title
  document.title = `${to.meta.title as string} | Auction Marketplace`

  // Initialize auth if not already done
  if (!authStore.initialized) {
    await authStore.initializeAuth()
  }

  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  // Check if route requires admin
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return next({ name: 'dashboard' })
  }

  // Redirect authenticated users away from guest pages
  if (to.meta.guest && authStore.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  next()
})

export default router