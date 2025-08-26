<template>
  <aside class="w-64 bg-white dark:bg-slate-800 h-screen shadow">
    <div class="p-4">
      <img src="@/assets/images/logo/logo.svg" alt="logo" class="h-8 mx-auto" />
    </div>
    <nav class="px-4">
      <ul>
        <li v-for="item in menuItems" :key="item.title" class="mb-2">
          <div>
            <button
              v-if="item.child"
              class="w-full flex justify-between items-center text-left font-semibold text-gray-700 dark:text-gray-100"
              @click="toggle(item.title)"
              :class="{ 'text-primary-500': isChildActive(item) }"
            >
              {{ item.title }}
              <span class="text-xs">{{ open[item.title] ? '-' : '+' }}</span>
            </button>
            <router-link
              v-else
              :to="item.link"
              class="block font-semibold text-gray-700 dark:text-gray-100"
              :class="{ 'text-primary-500': isActive(item.link) }"
            >
              {{ item.title }}
            </router-link>
          </div>
          <ul
            v-if="item.child && open[item.title]"
            class="mt-2 pl-4 text-sm text-gray-600 dark:text-gray-300"
          >
            <li v-for="child in item.child" :key="child.childlink" class="py-1">
              <router-link
                :to="child.childlink"
                class="block"
                :class="{ 'text-primary-500 font-semibold': isActive(child.childlink) }"
              >
                {{ child.childtitle }}
              </router-link>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </aside>
</template>
<script setup>
import { reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { menuItems } from '@/constant/data'

const route = useRoute()
const open = reactive({})

const isActive = (link) => route.path === link
const isChildActive = (item) =>
  item.child && item.child.some((c) => c.childlink === route.path)

const toggle = (title) => {
  open[title] = !open[title]
}

onMounted(() => {
  menuItems.forEach((item) => {
    if (isChildActive(item)) {
      open[item.title] = true
    }
  })
})
</script>
