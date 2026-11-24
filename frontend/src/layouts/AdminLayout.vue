<script setup lang="ts">
import { RouterLink, RouterView } from "vue-router";
import Navbar from "@/components/Navbar.vue";
import { useRoute } from "vue-router";
import { h } from "vue";

const route = useRoute();

const adminNavigation = [
  {
    name: "Dashboard",
    href: "/admin",
    icon: () =>
      h(
        "svg",
        {
          class: "h-5 w-5",
          fill: "none",
          stroke: "currentColor",
          viewBox: "0 0 24 24",
        },
        [
          h("path", {
            strokeLinecap: "round",
            strokeLinejoin: "round",
            strokeWidth: "2",
            d: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
          }),
        ],
      ),
  },
  {
    name: "Users",
    href: "/admin/users",
    icon: () =>
      h(
        "svg",
        {
          class: "h-5 w-5",
          fill: "none",
          stroke: "currentColor",
          viewBox: "0 0 24 24",
        },
        [
          h("path", {
            strokeLinecap: "round",
            strokeLinejoin: "round",
            strokeWidth: "2",
            d: "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
          }),
        ],
      ),
  },
  {
    name: "Products",
    href: "/admin/products",
    icon: () =>
      h(
        "svg",
        {
          class: "h-5 w-5",
          fill: "none",
          stroke: "currentColor",
          viewBox: "0 0 24 24",
        },
        [
          h("path", {
            strokeLinecap: "round",
            strokeLinejoin: "round",
            strokeWidth: "2",
            d: "M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4",
          }),
        ],
      ),
  },
  {
    name: "Auctions",
    href: "/admin/auctions",
    icon: () =>
      h(
        "svg",
        {
          class: "h-5 w-5",
          fill: "none",
          stroke: "currentColor",
          viewBox: "0 0 24 24",
        },
        [
          h("path", {
            strokeLinecap: "round",
            strokeLinejoin: "round",
            strokeWidth: "2",
            d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 002-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4",
          }),
        ],
      ),
  },
  {
    name: "Categories",
    href: "/admin/categories",
    icon: () =>
      h(
        "svg",
        {
          class: "h-5 w-5",
          fill: "none",
          stroke: "currentColor",
          viewBox: "0 0 24 24",
        },
        [
          h("path", {
            strokeLinecap: "round",
            strokeLinejoin: "round",
            strokeWidth: "2",
            d: "M7 7h10l-2 4h-6m-4 0H3v4a1 1 0 001 1h16a1 1 0 001-1V9a1 1 0 00-1-1H7m-4 0V5a1 1 0 011-1h16a1 1 0 011 1v4",
          }),
        ],
      ),
  },
  {
    name: "Reports",
    href: "/admin/reports",
    icon: () =>
      h(
        "svg",
        {
          class: "h-5 w-5",
          fill: "none",
          stroke: "currentColor",
          viewBox: "0 0 24 24",
        },
        [
          h("path", {
            strokeLinecap: "round",
            strokeLinejoin: "round",
            strokeWidth: "2",
            d: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m-4 0v-5m0 0l4-4m-4 4l-4-4",
          }),
        ],
      ),
  },
];
</script>

<template>
  <div class="min-h-screen bg-secondary-50 dark:bg-secondary-950">
    <Navbar />

    <div class="flex">
      <aside
        class="fixed inset-y-0 left-0 z-40 w-64 transform transition-transform duration-200 bg-white dark:bg-secondary-900 border-r border-secondary-200 dark:border-secondary-800 lg:relative lg:translate-x-0"
        aria-label="Admin sidebar"
      >
        <div class="flex flex-col h-full pt-16 lg:pt-0">
          <nav
            class="flex-1 p-4 space-y-1 overflow-y-auto"
            aria-label="Admin navigation"
          >
            <div
              class="px-3 py-2 text-xs font-semibold text-secondary-500 uppercase tracking-wider dark:text-secondary-400"
            >
              Administration
            </div>
            <RouterLink
              v-for="item in adminNavigation"
              :key="item.name"
              :to="item.href"
              class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-secondary-600 hover:bg-secondary-100 hover:text-primary-600 dark:text-secondary-400 dark:hover:bg-secondary-800 dark:hover:text-primary-400 transition-colors"
              :aria-current="route.path === item.href ? 'page' : undefined"
            >
              <component
                :is="item.icon"
                :class="{
                  'text-primary-600': route.path === item.href,
                  'text-secondary-400': route.path !== item.href,
                }"
                aria-hidden="true"
              />
              {{ item.name }}
            </RouterLink>
          </nav>

          <div
            class="p-4 border-t border-secondary-200 dark:border-secondary-800"
          >
            <RouterLink
              to="/dashboard"
              class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-secondary-600 hover:bg-secondary-100 hover:text-primary-600 dark:text-secondary-400 dark:hover:bg-secondary-800 dark:hover:text-primary-400 transition-colors"
            >
              <svg
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18"
                />
              </svg>
              User Dashboard
            </RouterLink>
          </div>
        </div>
      </aside>

      <main class="flex-1 lg:ml-64">
        <div class="p-6 lg:p-8">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped></style>
