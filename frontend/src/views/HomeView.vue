<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuctionStore } from '@/stores/auction'
import { useCategoryStore } from '@/stores/category'
import { formatCurrency, formatRelativeTime } from '@/utils/helpers'
import type { Auction, Category } from '@/types'
import { RouterLink } from 'vue-router'

const auctionStore = useAuctionStore()
const categoryStore = useCategoryStore()

const featuredAuctions = ref<Auction[]>([])
const liveAuctions = ref<Auction[]>([])
const upcomingAuctions = ref<Auction[]>([])
const recentProducts = ref<any[]>([])
const categories = ref<Category[]>([])
const loading = ref(true)

async function fetchHomeData() {
  loading.value = true
  try {
    const [featuredRes, liveRes, upcomingRes, categoriesRes] = await Promise.all([
      auctionStore.fetchAuctions({ status: 'active', per_page: 4, sort: '-current_price' }),
      auctionStore.fetchAuctions({ status: 'active', per_page: 6, sort: 'end_date' }),
      auctionStore.fetchAuctions({ status: 'scheduled', per_page: 4, sort: 'start_date' }),
      categoryStore.fetchCategories(),
    ])

    featuredAuctions.value = featuredRes.data
    liveAuctions.value = liveRes.data
    upcomingAuctions.value = upcomingRes.data
    categories.value = categoriesRes.data
  } catch (error) {
    console.error('Failed to fetch home data:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchHomeData()
})

const howItWorksSteps = [
  {
    title: 'Create Account',
    description: 'Sign up for free and verify your email to get started.',
  },
  {
    title: 'Browse & Bid',
    description: 'Explore live auctions, place bids, and watch in real-time.',
  },
  {
    title: 'Win & Pay',
    description: 'Win the auction, complete payment securely, and receive your item.',
  },
]

function formatTimeRemaining(endDate: string): string {
  const end = new Date(endDate)
  const now = new Date()
  const diff = Math.max(0, end.getTime() - now.getTime())

  const days = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  const minutes = Math.floor((diff % 3600000) / 60000)

  if (days > 0) return `${days}d ${hours}h`
  if (hours > 0) return `${hours}h ${minutes}m`
  return `${minutes}m`
}
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-secondary-950">
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-b from-primary-50 to-white dark:from-secondary-900 dark:to-secondary-950 py-20 lg:py-32">
      <div class="container">
        <div class="max-w-3xl mx-auto text-center">
          <h1 class="text-4xl font-bold tracking-tight text-secondary-900 dark:text-white sm:text-5xl lg:text-6xl">
            Discover Unique Items at
            <span class="gradient-text"> Live Auctions</span>
          </h1>
          <p class="mt-6 text-lg text-secondary-600 dark:text-secondary-300 max-w-2xl mx-auto">
            Bid on rare collectibles, art, antiques, and more. Join thousands of buyers and sellers in our trusted marketplace.
          </p>
          <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
            <router-link to="/auctions" class="btn btn-primary btn-lg w-full sm:w-auto">
              Browse Auctions
            </router-link>
            <router-link to="/register" class="btn btn-outline btn-lg w-full sm:w-auto">
              Start Selling
            </router-link>
          </div>
        </div>

        <!-- Stats -->
        <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">10,000+</div>
            <div class="text-secondary-600 dark:text-secondary-400">Active Bidders</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">5,000+</div>
            <div class="text-secondary-600 dark:text-secondary-400">Items Sold</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">$2.5M+</div>
            <div class="text-secondary-600 dark:text-secondary-400">Total Volume</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">99.9%</div>
            <div class="text-secondary-600 dark:text-secondary-400">Satisfaction</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section class="section">
      <div class="container">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="page-title">Popular Categories</h2>
            <p class="page-subtitle">Explore auctions by category</p>
          </div>
          <router-link to="/categories" class="link">View all categories</router-link>
        </div>

        <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
          <div v-for="i in 8" :key="i" class="skeleton aspect-square rounded-xl" />
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
          <router-link
            v-for="category in categories.slice(0, 8)"
            :key="category.id"
            :to="`/categories/${category.slug}`"
            class="group card p-6 text-center hover:shadow-card-hover transition-shadow"
          >
            <div class="w-16 h-16 mx-auto mb-4 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
              <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
            <h3 class="font-medium text-secondary-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ category.name }}</h3>
            <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">{{ category.active_auctions_count || 0 }} active auctions</p>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Live Auctions -->
    <section class="section bg-secondary-50 dark:bg-secondary-900/50">
      <div class="container">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="page-title">Live Auctions</h2>
            <p class="page-subtitle">Auctions ending soon</p>
          </div>
          <router-link to="/auctions?status=active" class="link">View all live auctions</router-link>
        </div>

        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="i in 3" :key="i" class="skeleton h-96 rounded-xl" />
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <article v-for="auction in liveAuctions" :key="auction.id" class="card overflow-hidden">
            <router-link :to="`/auctions/${auction.id}`" class="block">
              <div class="aspect-video bg-secondary-100 dark:bg-secondary-800 relative overflow-hidden">
                <img
                  v-if="auction.product?.images?.[0]?.url"
                  :src="auction.product.images[0].url"
                  :alt="auction.product.title"
                  class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-secondary-400">
                  <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="absolute top-3 left-3">
                  <span class="badge badge-danger animate-pulse">LIVE</span>
                </div>
                <div class="absolute bottom-3 right-3 bg-black/70 text-white px-3 py-1 rounded-lg text-sm font-medium">
                  Ends in {{ formatTimeRemaining(auction.end_date) }}
                </div>
              </div>
              <div class="p-4">
                <h3 class="font-semibold text-secondary-900 dark:text-white line-clamp-1 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                  {{ auction.product?.title }}
                </h3>
                <div class="mt-2 flex items-center justify-between">
                  <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                    {{ formatCurrency(auction.current_price, auction.product?.currency || 'USD') }}
                  </span>
                  <span class="text-sm text-secondary-500 dark:text-secondary-400">
                    {{ auction.total_bids }} bids
                  </span>
                </div>
              </div>
            </router-link>
          </article>
        </div>
      </div>
    </section>

    <!-- Upcoming Auctions -->
    <section class="section">
      <div class="container">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="page-title">Upcoming Auctions</h2>
            <p class="page-subtitle">Scheduled auctions starting soon</p>
          </div>
          <router-link to="/auctions?status=scheduled" class="link">View all upcoming</router-link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <article v-for="auction in upcomingAuctions" :key="auction.id" class="card overflow-hidden">
            <router-link :to="`/auctions/${auction.id}`" class="block">
              <div class="aspect-video bg-secondary-100 dark:bg-secondary-800 relative overflow-hidden">
                <img
                  v-if="auction.product?.images?.[0]?.url"
                  :src="auction.product.images[0].url"
                  :alt="auction.product.title"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-secondary-400">
                  <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="absolute top-3 left-3">
                  <span class="badge badge-warning">UPCOMING</span>
                </div>
              </div>
              <div class="p-4">
                <h3 class="font-semibold text-secondary-900 dark:text-white line-clamp-1">
                  {{ auction.product?.title }}
                </h3>
                <div class="mt-2 flex items-center justify-between">
                  <span class="text-xl font-bold text-secondary-900 dark:text-white">
                    {{ formatCurrency(auction.starting_price, auction.product?.currency || 'USD') }}
                  </span>
                  <span class="text-sm text-secondary-500 dark:text-secondary-400">
                    Starts {{ formatRelativeTime(auction.start_date) }}
                  </span>
                </div>
              </div>
            </router-link>
          </article>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section class="section bg-secondary-50 dark:bg-secondary-900/50">
      <div class="container">
        <div class="text-center mb-16">
          <h2 class="page-title">How It Works</h2>
          <p class="page-subtitle">Simple steps to start bidding and selling</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div v-for="(step, index) in howItWorksSteps" :key="index" class="text-center p-6">
            <div class="w-16 h-16 mx-auto mb-6 bg-primary-100 dark:bg-primary-900/30 rounded-2xl flex items-center justify-center">
              <span class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ index + 1 }}</span>
            </div>
            <h3 class="text-xl font-semibold text-secondary-900 dark:text-white mb-2">{{ step.title }}</h3>
            <p class="text-secondary-600 dark:text-secondary-400">{{ step.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="section">
      <div class="container">
        <div class="max-w-3xl mx-auto text-center p-12 bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl">
          <h2 class="text-3xl font-bold text-white mb-4">Ready to Start Bidding?</h2>
          <p class="text-primary-100 mb-8">Join thousands of buyers and sellers on the premier auction marketplace.</p>
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <router-link to="/register" class="btn btn-lg bg-white text-primary-700 hover:bg-primary-50 w-full sm:w-auto">
              Create Free Account
            </router-link>
            <router-link to="/auctions" class="btn btn-lg border-2 border-white text-white hover:bg-primary-800 w-full sm:w-auto">
              Browse Auctions
            </router-link>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped></style>