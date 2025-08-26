<template>
  <aside class="w-64 bg-white dark:bg-slate-800 h-screen shadow">
    <div class="p-4">
      <img src="@/assets/images/logo/logo.svg" alt="logo" class="h-8 mx-auto" />
    </div>
    <nav class="px-4">
      <ul>
        <li v-for="item in menuItems" :key="item.title" class="mb-4">
          <div class="font-semibold text-gray-700 dark:text-gray-100">{{ item.title }}</div>
          <ul v-if="item.child" class="mt-2 pl-4 text-sm text-gray-600 dark:text-gray-300">
            <li v-for="child in item.child" :key="child.childlink" class="py-1">
              {{ child.childtitle }}
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </aside>
</template>
<script setup>
import { menuItems } from '@/constant/data'
</script>
