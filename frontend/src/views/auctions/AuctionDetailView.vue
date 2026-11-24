<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuctionStore } from '@/stores/auction'
import { useRoute } from 'vue-router'
import { formatCurrency, formatRelativeTime, formatTimeRemaining } from '@/utils/helpers'
import type { Auction } from '@/types'
import { RouterLink } from 'vue-router'

const route = useRoute()
const auctionStore = useAuctionStore()
const auction = ref<Auction | null>(null)
const loading = ref(true)

async function fetchAuction() {
  loading.value = true
  try {
    const res = await auctionStore.fetchAuction(route.params.id as string)
    auction.value = res.data
  } catch (error) {
    console.error('Failed to fetch auction:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAuction()
})
</script>

<template>
  <div class="min-h-screen bg-secondary-50 dark:bg-secondary-950">
    <div v-if="loading" class="container py-16">
      <div class="skeleton h-96 rounded-xl mb-8" />
      <div class="skeleton h-32 rounded-lg" />
    </div>

    <div v-else-if="auction" class="container py-8">
      <RouterLink to="/auctions" class="link mb-6 inline-block">← Back to Auctions</RouterLink>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
          <div class="aspect-video bg-secondary-100 dark:bg-secondary-800 rounded-xl overflow-hidden mb-6">
            <img
              v-if="auction.product?.images?.[0]?.url"
              :src="auction.product.images[0].url"
              :alt="auction.product.title"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-secondary-400">
              <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <h1 class="text-3xl font-bold text-secondary-900 dark:text-white mb-4">{{ auction.product?.title }}</h1>
          <p class="text-secondary-600 dark:text-secondary-400">{{ auction.product?.description }}</p>
        </div>

        <div class="card p-6 sticky top-24">
          <div class="mb-6">
            <span class="badge badge-danger" v-if="auction.status === 'active'">LIVE</span>
            <span class="badge badge-warning" v-else-if="auction.status === 'scheduled'">UPCOMING</span>
            <span class="badge badge-secondary" v-else>{{ auction.status }}</span>
          </div>

          <div class="text-4xl font-bold text-primary-600 dark:text-primary-400 mb-2">
            {{ formatCurrency(auction.current_price, auction.product?.currency || 'USD') }}
          </div>
          <div class="text-secondary-500 dark:text-secondary-400 mb-6">
            {{ auction.total_bids }} bids · Ends in {{ formatTimeRemaining(auction.end_date) }}
          </div>

          <button class="btn btn-primary w-full btn-lg" disabled>
            Place Bid
          </button>
        </div>
      </div>
    </div>

    <div v-else class="container py-16 text-center">
      <h2 class="text-2xl font-bold text-secondary-900 dark:text-white">Auction not found</h2>
      <RouterLink to="/auctions" class="btn btn-primary mt-4">Browse Auctions</RouterLink>
    </div>
  </div>
</template>

<style scoped></style>