<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-secondary-50 dark:bg-secondary-950 px-4">
    <div class="text-center">
      <h1 class="text-9xl font-bold text-primary-600 dark:text-primary-400">404</h1>
      <h2 class="mt-4 text-3xl font-semibold text-secondary-900 dark:text-white">Page Not Found</h2>
      <p class="mt-2 text-lg text-secondary-600 dark:text-secondary-400 max-w-md mx-auto">
        Sorry, we couldn't find the page you're looking for. It might have been moved or doesn't exist.
      </p>
      <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
        <RouterLink to="/" class="btn btn-primary btn-lg">
          Go Home
        </RouterLink>
        <RouterLink to="/auctions" class="btn btn-outline btn-lg">
          Browse Auctions
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<style scoped></style>