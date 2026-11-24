<script setup lang="ts">
interface Props {
  categories: any[]
  selectedId: string | null
  level?: number
}

interface Emits {
  select: [id: string]
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const level = props.level || 0
</script>

<template>
  <ul class="space-y-1">
    <li v-for="category in categories" :key="category.id">
      <button
        @click="emit('select', category.id)"
        :class="['w-full text-left px-3 py-2 rounded-lg transition-colors flex items-center justify-between', selectedId === category.id ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium' : 'hover:bg-secondary-100 dark:hover:bg-secondary-800']"
        :style="{ paddingLeft: (level + 1) * 16 + 'px' }"
      >
        <span class="flex items-center gap-2">
          <span v-if="category.children && category.children.length > 0" class="text-secondary-400">
            ▼
          </span>
          {{ category.name }}
        </span>
        <span class="text-xs text-secondary-400" v-if="category.products_count">
          {{ category.products_count }}
        </span>
      </button>
      <CategoryTree
        v-if="category.children && category.children.length > 0"
        :categories="category.children"
        :selected-id="selectedId"
        :level="level + 1"
        @select="emit('select')"
      />
    </li>
  </ul>
</template>

<style scoped></style>