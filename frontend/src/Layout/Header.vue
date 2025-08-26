<template>
  <header class="bg-white dark:bg-slate-800 shadow px-4 h-16 flex items-center">
    <img src="@/assets/images/logo/logo.svg" alt="logo" class="h-8" />
    <button class="ml-auto md:hidden" @click="show = !show">Menu</button>
    <nav :class="['ml-auto', show ? 'block' : 'hidden', 'md:block']">
      <ul class="flex space-x-4">
        <li v-for="item in topMenu" :key="item.title">
          <router-link
            :to="item.link"
            class="text-gray-700 dark:text-gray-100"
            :class="{ 'font-semibold text-primary-500': isActive(item.link) }"
          >
            {{ item.title }}
          </router-link>
        </li>
      </ul>
    </nav>
  </header>
</template>
<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { topMenu } from '@/constant/data'

const show = ref(false)
const route = useRoute()
const isActive = (link) => route.path === link
</script>

