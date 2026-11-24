<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useCategoryStore } from '@/stores/category'
import { RouterLink } from 'vue-router'
import type { Category } from '@/types'

const categoryStore = useCategoryStore()
const categories = ref<Category[]>([])
const loading = ref(true)
const searchQuery = ref('')

async function fetchCategories() {
  loading.value = true
  try {
    const res = await categoryStore.fetchCategories({ is_active: true })
    categories.value = res.data
  } catch (error) {
    console.error('Failed to fetch categories:', error)
  } finally {
    loading.value = false
  }
}

function getRootCategories(): Category[] {
  return categories.value.filter(c => c.parent_id === null)
}

function getChildCategories(parentId: string | null): Category[] {
  return categories.value.filter(c => c.parent_id === parentId)
}

const filteredCategories = computed(() => {
  if (!searchQuery.value) return categories.value
  const query = searchQuery.value.toLowerCase()
  return categories.value.filter(c =>
    c.name.toLowerCase().includes(query) ||
    c.description?.toLowerCase().includes(query)
  )
})

onMounted(() => {
  fetchCategories()
})
</script>

<template>
  <div class="min-h-screen bg-secondary-50 dark:bg-secondary-950">
    <div class="container py-8">
      <div class="flex items-center justify-between mb-8">
        <h1 class="page-title">Categories</h1>
        <div class="relative max-w-xs">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search categories..."
            class="w-full pl-10 pr-4 py-2 border border-secondary-300 dark:border-secondary-600 rounded-lg bg-white dark:bg-secondary-800 text-secondary-900 dark:text-white placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
          />
        </div>
      </div>

      <div v-if="loading" class="space-y-4">
        <div v-for="i in 10" :key="i" class="skeleton h-16 rounded-xl" />
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <RouterLink v-for="category in getRootCategories()" :key="category.id" :to="`/categories/${category.slug}`" class="card p-6 group hover:shadow-lg transition-shadow">
          <div class="flex items-start gap-4">
            <div class="w-16 h-16 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
              <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-secondary-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                {{ category.name }}
              </h3>
              <p v-if="category.description" class="mt-1 text-sm text-secondary-500 dark:text-secondary-400 line-clamp-2">
                {{ category.description }}
              </p>
              <div class="mt-3 flex items-center gap-4 text-sm text-secondary-400">
                <span v-if="category.products_count !== undefined">
                  {{ category.products_count }} products
                </span>
                <span v-if="category.children && category.children.length > 0">
                  {{ category.children.length }} subcategories
                </span>
              </div>
            </div>
            <svg class="w-5 h-5 text-secondary-400 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </RouterLink>
      </div>

      <div v-if="!loading && categories.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="mt-2 text-lg font-medium text-secondary-900 dark:text-white">No categories found</h3>
        <p class="mt-1 text-secondary-500 dark:text-secondary-400">Categories will appear here once created.</p>
      </div>
    </div>
  </div>
</template>

<style scoped></style>