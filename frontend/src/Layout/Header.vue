<template>
  <header class="bg-white dark:bg-slate-800 shadow px-4 h-16 flex items-center">
    <img src="@/assets/images/logo/logo.svg" alt="logo" class="h-8" />
    <button class="ml-auto md:hidden" @click="show = !show">Menu</button>
    <div class="ml-auto flex items-center space-x-4">
      <nav :class="[show ? 'block' : 'hidden', 'md:block']">
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
      <div v-if="auth.user" class="relative">
        <button
          class="flex items-center space-x-2 text-gray-700 dark:text-gray-100"
          @click="profileOpen = !profileOpen"
        >
          <span>{{ auth.user.name }}</span>
        </button>
        <div
          v-if="profileOpen"
          class="absolute right-0 mt-2 w-32 bg-white dark:bg-slate-700 border rounded shadow"
        >
          <button
            @click="logout"
            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-slate-600"
          >
            Logout
          </button>
        </div>
      </div>
    </div>
  </header>
</template>
<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { topMenu } from '@/constant/data'
import { useAuthStore } from '@/stores/auth'

const show = ref(false)
const profileOpen = ref(false)
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const isActive = (link) => route.path === link

const logout = async () => {
  await auth.logout()
  router.push('/auth/login')
}
</script>
