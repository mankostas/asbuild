<template>
  <div>
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
        <button
          class="text-gray-700 dark:text-gray-100"
          @click="ui.toggleTheme"
        >
          <i :class="ui.theme === 'dark' ? 'pi pi-sun' : 'pi pi-moon'"></i>
        </button>
        <Bell />
        <input
          v-if="dev"
          v-model="tenantId"
          class="border rounded px-1 py-0.5 text-sm w-24"
          placeholder="Tenant"
        />
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
            <router-link
              to="/settings/profile"
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-slate-600"
              @click="profileOpen = false"
            >
              Profile
            </router-link>
            <router-link
              v-if="isAdmin"
              to="/settings/branding"
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-slate-600"
              @click="profileOpen = false"
            >
              Branding
            </router-link>
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
    <div
      v-if="auth.impersonatedTenant"
      class="bg-yellow-100 text-yellow-800 text-center py-2"
    >
      Impersonating Tenant {{ auth.impersonatedTenant }}
      <button class="underline ml-2" @click="logout">
        Stop impersonation
      </button>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { topMenu } from '@/constant/data'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/store/ui'
import { useTenantStore } from '@/store/tenant'
import Bell from '@/components/notifications/Bell.vue'

const show = ref(false)
const profileOpen = ref(false)
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()
const tenant = useTenantStore()
const tenantId = ref(tenant.tenantId)
const dev = import.meta.env.DEV
const isAdmin = computed(() =>
  auth.user?.roles?.some((r) => ['ClientAdmin', 'SuperAdmin'].includes(r.name))
)
watch(tenantId, (id) => tenant.setTenant(id))
const isActive = (link) => route.path === link

const logout = async () => {
  await auth.logout()
  router.push('/auth/login')
}
</script>
