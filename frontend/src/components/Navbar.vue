<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { useThemeStore } from '@/stores/theme'
import { useAuthStore } from '@/stores/auth'

const themeStore = useThemeStore()
const authStore = useAuthStore()

const navigation = [
  { name: 'Home', href: '/' },
  { name: 'Auctions', href: '/auctions' },
  { name: 'Categories', href: '/categories' },
]

const userNavigation = [
  { name: 'Dashboard', href: '/dashboard' },
  { name: 'Profile', href: '/dashboard/profile' },
]

const adminNavigation = [
  { name: 'Admin Dashboard', href: '/admin' },
  { name: 'Users', href: '/admin/users' },
  { name: 'Products', href: '/admin/products' },
  { name: 'Auctions', href: '/admin/auctions' },
  { name: 'Categories', href: '/admin/categories' },
  { name: 'Reports', href: '/admin/reports' },
]
</script>

<template>
  <header class="sticky top-0 z-50 w-full border-b border-secondary-200 dark:border-secondary-800 bg-white/80 dark:bg-secondary-950/80 backdrop-blur-lg">
    <nav class="container" aria-label="Global">
      <div class="flex h-16 items-center justify-between">
        <div class="flex items-center gap-8">
          <RouterLink to="/" class="flex items-center gap-2 text-xl font-bold text-secondary-900 dark:text-white" aria-label="Auction Marketplace Home">
            <svg class="h-8 w-8 text-primary-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" fill="none"/>
            </svg>
            <span>AuctionHub</span>
          </RouterLink>

          <div class="hidden md:flex md:gap-6">
            <RouterLink v-for="item in navigation" :key="item.name" :to="item.href" class="text-sm font-medium text-secondary-600 hover:text-primary-600 dark:text-secondary-400 dark:hover:text-primary-400 transition-colors">
              {{ item.name }}
            </RouterLink>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <button @click="themeStore.toggleTheme" class="p-2 rounded-lg text-secondary-600 hover:bg-secondary-100 dark:text-secondary-400 dark:hover:bg-secondary-800 transition-colors" aria-label="Toggle theme">
            <svg v-if="!themeStore.isDark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </button>

          <div v-if="authStore.isAuthenticated" class="hidden md:flex md:items-center md:gap-4">
            <RouterLink v-for="item in userNavigation" :key="item.name" :to="item.href" class="text-sm font-medium text-secondary-600 hover:text-primary-600 dark:text-secondary-400 dark:hover:text-primary-400 transition-colors">
              {{ item.name }}
            </RouterLink>

            <RouterLink v-if="authStore.isAdmin" v-for="item in adminNavigation" :key="item.name" :to="item.href" class="text-sm font-medium text-secondary-600 hover:text-primary-600 dark:text-secondary-400 dark:hover:text-primary-400 transition-colors">
              {{ item.name }}
            </RouterLink>

            <button @click="authStore.logout" class="btn btn-ghost btn-sm">
              Logout
            </button>
          </div>

          <div v-else class="hidden md:flex md:items-center md:gap-2">
            <RouterLink to="/login" class="btn btn-ghost btn-sm">Login</RouterLink>
            <RouterLink to="/register" class="btn btn-primary btn-sm">Register</RouterLink>
          </div>

          <button class="md:hidden p-2 rounded-lg text-secondary-600 hover:bg-secondary-100 dark:text-secondary-400 dark:hover:bg-secondary-800" aria-label="Toggle menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>
    </nav>
  </header>
</template>

<style scoped></style>