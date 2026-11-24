<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppToast } from '@/composables/useToast'
import { RouterLink } from 'vue-router'

const router = useRouter()
const authStore = useAuthStore()
const toast = useAppToast()

const email = ref('')
const loading = ref(false)
const errors = ref<Record<string, string>>({})
const submitted = ref(false)

async function handleSubmit() {
  errors.value = {}
  loading.value = true

  try {
    await authStore.forgotPassword(email.value)
    submitted.value = true
    toast.success('If an account exists, a password reset link has been sent.')
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Failed to send reset link.')
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
        <h1 class="text-2xl font-bold text-secondary-900 dark:text-white">Forgot password?</h1>
        <p class="mt-2 text-secondary-600 dark:text-secondary-400">Enter your email and we'll send you a reset link.</p>
      </div>

      <div class="card">
        <div class="card-body">
          <template v-if="!submitted">
            <form @submit.prevent="handleSubmit" class="space-y-6">
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

              <button type="submit" :disabled="loading" class="btn btn-primary w-full">
                <span v-if="loading" class="flex items-center justify-center gap-2">
                  <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  Sending...
                </span>
                <span v-else>Send reset link</span>
              </button>
            </form>
          </template>

          <template v-else>
            <div class="text-center py-4">
              <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-secondary-900 dark:text-white mb-2">Check your email</h3>
              <p class="text-secondary-600 dark:text-secondary-400 mb-6">
                We've sent a password reset link to <strong>{{ email }}</strong>. The link expires in 60 minutes.
              </p>
              <RouterLink to="/login" class="btn btn-primary">Back to login</RouterLink>
            </div>
          </template>

          <div class="mt-6 text-center">
            <RouterLink to="/login" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">Back to login</RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>