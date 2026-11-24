<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppToast } from '@/composables/useToast'
import { RouterLink } from 'vue-router'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toast = useAppToast()

const email = ref('')
const password = ref('')
const remember = ref(false)
const loading = ref(false)
const errors = ref<Record<string, string>>({})

const redirect = route.query.redirect as string || '/dashboard'

async function handleLogin() {
  errors.value = {}
  loading.value = true

  try {
    await authStore.login(email.value, password.value)
    toast.success('Welcome back!')
    router.push(redirect)
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Login failed. Please try again.')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-secondary-50 dark:bg-secondary-950 px-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <RouterLink to="/" class="inline-flex items-center gap-2 text-2xl font-bold text-secondary-900 dark:text-white mb-6">
          <svg class="h-10 w-10 text-primary-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" fill="none"/>
          </svg>
          <span>AuctionHub</span>
        </RouterLink>
        <h1 class="text-2xl font-bold text-secondary-900 dark:text-white">Welcome back</h1>
        <p class="mt-2 text-secondary-600 dark:text-secondary-400">Sign in to your account to continue</p>
      </div>

      <div class="card">
        <div class="card-body">
          <form @submit.prevent="handleLogin" class="space-y-6">
            <div>
              <label for="email" class="label">Email Address</label>
              <input
                id="email"
                v-model="email"
                type="email"
                autocomplete="email"
                class="input"
                :class="{ 'input-error': errors.email }"
                placeholder="you@example.com"
                :disabled="loading"
                required
              />
              <p v-if="errors.email" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ errors.email }}</p>
            </div>

            <div>
              <div class="flex items-center justify-between">
                <label for="password" class="label mb-0">Password</label>
                <RouterLink to="/forgot-password" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">Forgot password?</RouterLink>
              </div>
              <input
                id="password"
                v-model="password"
                type="password"
                autocomplete="current-password"
                class="input"
                :class="{ 'input-error': errors.password }"
                placeholder="••••••••"
                :disabled="loading"
                required
              />
              <p v-if="errors.password" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ errors.password }}</p>
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="remember"
                  type="checkbox"
                  class="w-4 h-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
                />
                <span class="text-sm text-secondary-600 dark:text-secondary-400">Remember me</span>
              </label>
            </div>

            <button type="submit" :disabled="loading" class="btn btn-primary w-full">
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                Signing in...
              </span>
              <span v-else>Sign in</span>
            </button>
          </form>

          <div class="mt-6 text-center">
            <p class="text-secondary-600 dark:text-secondary-400">
              Don't have an account? <RouterLink to="/register" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">Sign up</RouterLink>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>