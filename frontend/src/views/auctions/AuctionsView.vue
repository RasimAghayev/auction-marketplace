<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuctionStore } from '@/stores/auction'
import { useCategoryStore } from '@/stores/category'
import { RouterLink } from 'vue-router'
import type { Auction, Category, AuctionFilters } from '@/types'
import CategoryTree from '@/components/CategoryTree.vue'

const auctionStore = useAuctionStore()
const categoryStore = useCategoryStore()

const auctions = ref<Auction[]>([])
const loading = ref(true)
const categories = ref<Category[]>([])
const selectedCategory = ref<string | null>(null)
const showCategoryTree = ref(true)

const pagination = ref({ page: 1, per_page: 12, total: 0, last_page: 1 })
const filters = ref<AuctionFilters>({
  status: 'active',
  sort: '-current_price',
  page: 1,
  per_page: 12,
})

async function fetchCategories() {
  try {
    const res = await categoryStore.fetchCategories({ is_active: true })
    categories.value = res.data
  } catch (error) {
    console.error('Failed to fetch categories:', error)
  }
}

async function fetchAuctions() {
  loading.value = true
  try {
    if (selectedCategory.value) {
      filters.value.category_id = selectedCategory.value
    } else {
      delete filters.value.category_id
    }
    filters.value.page = pagination.value.page
    filters.value.per_page = pagination.value.per_page

    const res = await auctionStore.fetchAuctions(filters.value)
    auctions.value = res.data
    pagination.value = { ...pagination.value, ...res.meta }
  } catch (error) {
    console.error('Failed to fetch auctions:', error)
  } finally {
    loading.value = false
  }
}

function getChildCategories(parentId: string | null): Category[] {
  return categories.value.filter(c => c.parent_id === parentId)
}

function getRootCategories(): Category[] {
  return categories.value.filter(c => c.parent_id === null)
}

function selectCategory(categoryId: string | null) {
  selectedCategory.value = categoryId
  pagination.value.page = 1
  fetchAuctions()
}

function clearCategoryFilter() {
  selectedCategory.value = null
  pagination.value.page = 1
  fetchAuctions()
}

const selectedCategoryName = computed(() => {
  if (!selectedCategory.value) return 'All Categories'
  const cat = categories.value.find(c => c.id === selectedCategory.value)
  return cat?.name || 'All Categories'
})

onMounted(() => {
  fetchCategories()
  fetchAuctions()
})
</script>

<template>
  <div class="min-h-screen bg-secondary-50 dark:bg-secondary-950">
    <div class="container py-8">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <h1 class="page-title">Browse Auctions</h1>
          <span v-if="selectedCategory" class="badge badge-primary">
            {{ selectedCategoryName }} <button @click="clearCategoryFilter" class="ml-2">×</button>
          </span>
        </div>
        <button @click="showCategoryTree = !showCategoryTree" class="btn btn-outline lg:hidden">
          {{ showCategoryTree ? 'Hide' : 'Show' }} Categories
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Category Sidebar -->
        <aside v-show="showCategoryTree" class="lg:block hidden lg:sticky lg:top-24 lg:h-fit">
          <div class="card p-4">
            <h3 class="font-semibold text-secondary-900 dark:text-white mb-4">Categories</h3>
            <button
              @click="clearCategoryFilter"
              :class="['w-full text-left px-3 py-2 rounded-lg transition-colors mb-2', selectedCategory === null ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium' : 'hover:bg-secondary-100 dark:hover:bg-secondary-800']"
            >
              All Categories
            </button>
            <nav class="space-y-1">
              <CategoryTree :categories="getRootCategories()" :selected-id="selectedCategory" @select="selectCategory" />
            </nav>
          </div>
        </aside>

        <!-- Auctions Grid -->
        <main class="lg:col-span-3 min-w-0">
          <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="i in 6" :key="i" class="skeleton h-96 rounded-xl" />
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                    <span :class="['badge', auction.status === 'active' ? 'badge-danger' : auction.status === 'scheduled' ? 'badge-warning' : 'badge-secondary']">
                      {{ auction.status }}
                    </span>
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

          <div v-if="auctions.length === 0 && !loading" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-secondary-900 dark:text-white">No auctions found</h3>
            <p class="mt-1 text-secondary-500 dark:text-secondary-400">Try adjusting your filters or browse all categories.</p>
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
        </main>
      </div>
    </div>
  </div>
</template>

<style scoped></style>