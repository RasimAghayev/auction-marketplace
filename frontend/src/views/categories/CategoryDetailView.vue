<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useCategoryStore } from '@/stores/category'
import { useAuctionStore } from '@/stores/auction'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import type { Category, Auction } from '@/types'

const categoryStore = useCategoryStore()
const auctionStore = useAuctionStore()
const route = useRoute()
const router = useRouter()

const category = ref<Category | null>(null)
const subcategories = ref<Category[]>([])
const auctions = ref<Auction[]>([])
const loading = ref(true)
const pagination = ref({ page: 1, per_page: 12, total: 0, last_page: 1 })

async function fetchCategory() {
  loading.value = true
  try {
    const cat = await categoryStore.fetchCategory(route.params.slug as string)
    category.value = cat
    subcategories.value = cat.children || []
    await fetchAuctions()
  } catch (error) {
    console.error('Failed to fetch category:', error)
  } finally {
    loading.value = false
  }
}

async function fetchAuctions() {
  if (!category.value) return
  try {
    const res = await auctionStore.fetchAuctions({
      category_id: category.value.id,
      status: 'active',
      page: pagination.value.page,
      per_page: pagination.value.per_page,
      sort: '-current_price',
    })
    auctions.value = res.data
    pagination.value = { ...pagination.value, ...res.meta }
  } catch (error) {
    console.error('Failed to fetch auctions:', error)
  }
}

function navigateToSubcategory(slug: string) {
  router.push(`/categories/${slug}`)
}
</script>

<template>
  <div class="min-h-screen bg-secondary-50 dark:bg-secondary-950">
    <div class="container py-8">
      <div v-if="loading" class="space-y-6">
        <div class="skeleton h-32 rounded-xl w-3/4" />
        <div class="skeleton h-16 rounded-xl" />
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="i in 6" :key="i" class="skeleton h-96 rounded-xl" />
        </div>
      </div>

      <div v-else-if="category" class="space-y-8">
        <!-- Category Header -->
        <div class="card p-6">
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
              <svg class="w-10 h-10 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div>
              <h1 class="text-3xl font-bold text-secondary-900 dark:text-white">{{ category.name }}</h1>
              <p v-if="category.description" class="mt-2 text-secondary-500 dark:text-secondary-400">{{ category.description }}</p>
              <div class="mt-4 flex items-center gap-6 text-sm text-secondary-400">
                <span v-if="category.products_count !== undefined">
                  {{ category.products_count }} products
                </span>
                <span v-if="category.children && category.children.length > 0">
                  {{ category.children.length }} subcategories
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Subcategories -->
        <div v-if="subcategories.length > 0" class="space-y-4">
          <h2 class="text-xl font-semibold text-secondary-900 dark:text-white">Subcategories</h2>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <RouterLink v-for="sub in subcategories" :key="sub.id" :to="`/categories/${sub.slug}`" class="card p-4 hover:shadow-md transition-shadow">
              <h3 class="font-medium text-secondary-900 dark:text-white">{{ sub.name }}</h3>
              <p v-if="sub.products_count !== undefined" class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
                {{ sub.products_count }} products
              </p>
            </RouterLink>
          </div>
        </div>

        <!-- Auctions in this category -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-secondary-900 dark:text-white">Active Auctions</h2>
            <span class="text-sm text-secondary-500 dark:text-secondary-400">{{ pagination.total }} auctions</span>
          </div>

          <div v-if="auctions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <article v-for="auction in auctions" :key="auction.id" class="card overflow-hidden">
              <RouterLink :to="`/auctions/${auction.id}`" class="block">
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
                    <span class="badge badge-danger">{{ auction.status }}</span>
                  </div>
                </div>
                <div class="p-4">
                  <h3 class="font-semibold text-secondary-900 dark:text-white line-clamp-1">{{ auction.product?.title }}</h3>
                  <div class="mt-2 flex items-center justify-between">
                    <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                      {{ auction.current_price }} {{ auction.product?.currency || 'USD' }}
                    </span>
                    <span class="text-sm text-secondary-500 dark:text-secondary-400">{{ auction.total_bids }} bids</span>
                  </div>
                </div>
              </RouterLink>
            </article>
          </div>

          <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-secondary-900 dark:text-white">No active auctions in this category</h3>
            <p class="mt-1 text-secondary-500 dark:text-secondary-400">Check back later or browse other categories.</p>
          </div>

          <div v-if="pagination.last_page > 1" class="mt-8 flex items-center justify-center gap-2">
            <button
              @click="pagination.page--"
              :disabled="pagination.page <= 1"
              class="btn btn-outline"
            >Previous</button>
            <span class="px-4 text-secondary-600 dark:text-secondary-400">
              Page {{ pagination.page }} of {{ pagination.last_page }}
            </span>
            <button
              @click="pagination.page++"
              :disabled="pagination.page >= pagination.last_page"
              class="btn btn-outline"
            >Next</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>