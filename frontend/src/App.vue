<script setup lang="ts">
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import Toast from 'vue-toastification'

const authStore = useAuthStore()
const themeStore = useThemeStore()

// Initialize auth and theme on app start
authStore.initializeAuth()
themeStore.initializeTheme()
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-secondary-950 transition-colors duration-200">
    <RouterView />
  </div>
</template>

<style scoped></style>