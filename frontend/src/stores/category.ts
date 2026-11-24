import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import type { Category, PaginatedResponse } from '@/types'

export const useCategoryStore = defineStore('category', () => {
  const categories = ref<Category[]>([])
  const currentCategory = ref<Category | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchCategories(params: Record<string, any> = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get<PaginatedResponse<Category>>('/api/v1/categories', { params })
      categories.value = response.data.data
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch categories'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchCategory(slug: string) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get<{ category: Category }>(`/api/v1/categories/${slug}`)
      currentCategory.value = response.data.category
      return response.data.category
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch category'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createCategory(data: Partial<Category>) {
    try {
      const response = await axios.post<{ category: Category }>('/api/v1/admin/categories', data)
      categories.value.push(response.data.category)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create category'
      throw err
    }
  }

  async function updateCategory(id: string, data: Partial<Category>) {
    try {
      const response = await axios.put<{ category: Category }>(`/api/v1/admin/categories/${id}`, data)
      const index = categories.value.findIndex(c => c.id === id)
      if (index !== -1) {
        categories.value[index] = response.data.category
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update category'
      throw err
    }
  }

  async function deleteCategory(id: string) {
    try {
      await axios.delete(`/api/v1/admin/categories/${id}`)
      categories.value = categories.value.filter(c => c.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete category'
      throw err
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    categories,
    currentCategory,
    loading,
    error,
    fetchCategories,
    fetchCategory,
    createCategory,
    updateCategory,
    deleteCategory,
    clearError,
  }
})