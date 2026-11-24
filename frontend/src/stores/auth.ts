import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import type { User } from '@/types'

interface AuthState {
  user: User | null
  token: string | null
  initialized: boolean
}

export const useAuthStore = defineStore('auth', () => {
  const state = ref<AuthState>({
    user: null,
    token: localStorage.getItem('auth_token'),
    initialized: false,
  })

  const isAuthenticated = computed(() => !!state.value.token && !!state.value.user)
  const isAdmin = computed(() => state.value.user?.role === 'admin')
  const isSeller = computed(() => state.value.user?.role === 'seller')

  function setAuth(user: User, token: string) {
    state.value.user = user
    state.value.token = token
    localStorage.setItem('auth_token', token)
    axios.defaults.headers.common.Authorization = `Bearer ${token}`
  }

  function clearAuth() {
    state.value.user = null
    state.value.token = null
    localStorage.removeItem('auth_token')
    delete axios.defaults.headers.common.Authorization
  }

  async function initializeAuth() {
    const token = localStorage.getItem('auth_token')
    if (token) {
      axios.defaults.headers.common.Authorization = `Bearer ${token}`
      try {
        const response = await axios.get('/api/v1/auth/user')
        state.value.user = response.data.user
      } catch {
        clearAuth()
      }
    }
    state.value.initialized = true
  }

  async function login(email: string, password: string) {
    const response = await axios.post('/api/v1/auth/login', { email, password })
    setAuth(response.data.user, response.data.token)
    return response.data
  }

  async function register(data: { name: string; email: string; password: string; password_confirmation: string }) {
    const response = await axios.post('/api/v1/auth/register', data)
    return response.data
  }

  async function logout() {
    try {
      await axios.post('/api/v1/auth/logout')
    } finally {
      clearAuth()
    }
  }

  async function updateProfile(data: Partial<User>) {
    const response = await axios.put('/api/v1/auth/profile', data)
    state.value.user = response.data.user
    return response.data
  }

  async function updatePassword(data: { current_password: string; password: string; password_confirmation: string }) {
    const response = await axios.put('/api/v1/auth/password', data)
    return response.data
  }

  async function forgotPassword(email: string) {
    const response = await axios.post('/api/v1/auth/forgot-password', { email })
    return response.data
  }

  async function resetPassword(data: { token: string; email: string; password: string; password_confirmation: string }) {
    const response = await axios.post('/api/v1/auth/reset-password', data)
    return response.data
  }

  return {
    state,
    isAuthenticated,
    isAdmin,
    isSeller,
    setAuth,
    clearAuth,
    initializeAuth,
    login,
    register,
    logout,
    updateProfile,
    updatePassword,
    forgotPassword,
    resetPassword,
  }
})