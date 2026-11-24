<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppToast } from '@/composables/useToast'
import { RouterLink } from 'vue-router'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toast = useAppToast()

const form = ref({
  token: route.query.token as string || '',
  email: route.query.email as string || '',
  password: '',
  password_confirmation: '',
})
const loading = ref(false)
const errors = ref<Record<string, string>>({})

onMounted(() => {
  if (!form.value.token) {
    toast.error('Invalid or missing reset token.')
    router.push('/forgot-password')
  }
})

async function handleSubmit() {
  errors.value = {}
  loading.value = true

  try {
    await authStore.resetPassword(form.value)
    toast.success('Password has been reset successfully.')
    router.push('/login')
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Failed to reset password.')
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
        <h1 class="text-2xl font-bold text-secondary-900 dark:text-white">Reset your password</h1>
        <p class="mt-2 text-secondary-600 dark:text-secondary-400">Enter your new password below.</p>
      </div>

      <div class="card">
        <div class="card-body">
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <input type="hidden" v-model="form.token" />
            <input type="hidden" v-model="form.email" />

            <div>
              <label for="password" class="label">New Password</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                class="input"
                :class="{ 'input-error': errors.password }"
                placeholder="••••••••"
                :disabled="loading"
                required
                minlength="8"
              />
              <p v-if="errors.password" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ errors.password }}</p>
              <p class="mt-1.5 text-xs text-secondary-500 dark:text-secondary-400">
                At least 8 characters with uppercase, lowercase, number, and symbol
              </p>
            </div>

            <div>
              <label for="password_confirmation" class="label">Confirm New Password</label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                class="input"
                :class="{ 'input-error': errors.password_confirmation }"
                placeholder="••••••••"
                :disabled="loading"
                required
              />
              <p v-if="errors.password_confirmation" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ errors.password_confirmation }}</p>
            </div>

            <button type="submit" :disabled="loading" class="btn btn-primary w-full">
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                Resetting...
              </span>
              <span v-else>Reset password</span>
            </button>
          </form>

          <div class="mt-6 text-center">
            <RouterLink to="/login" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">Back to login</RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>